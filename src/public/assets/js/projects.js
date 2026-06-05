const Projects = {
  isSubmitting: false,

  loadProjects() {
    ApiClient.getProjects()
      .done((projects) => {
        App.projects = projects.map((p) => ({
          ...p,
          porcentaje_avance: p.porcentaje_avance !== undefined ? parseInt(p.porcentaje_avance) : 0,
        }));
        this.renderProjects();
        this.renderSummary();
        this.populateResponsibleFilter();
      })
      .fail((xhr) => {
        const msg = xhr.responseJSON?.error || "Erro ao carregar projetos.";
        App.showFeedback(msg, "danger", "#dashboard-feedback");
      });
  },

  populateResponsibleFilter() {
    const validManagers = App.users.filter(
      (u) => u.rol === "jefe_proyecto" || u.rol === "administrador"
    );
    const options = validManagers
      .map((u) => `<option value="${u.id}">${u.nombre} ${u.apellidos}</option>`)
      .join("");
    const optionsHtml = '<option value="">Todos</option>' + options;
    $("#project-responsible-filter").html(optionsHtml);
    $("#filter-responsible").html(optionsHtml);
  },

  renderProjects() {
    this.applyFiltersSilent();
  },

  applyFiltersSilent() {
    const search = ($("#filter-search").val() || "").toLowerCase();
    const estadoFilter = $("#filter-state").val();
    const responsibleFilter = $("#filter-responsible").val();
    const dateOrder = $("#filter-date-order").val();
    const deadlineOrder = $("#filter-deadline-order").val();

    let filtered = App.projects.filter(
      (p) => p.nombre.toLowerCase().includes(search) &&
             (!estadoFilter || p.estado === estadoFilter) &&
             (!responsibleFilter || p.responsable_id == responsibleFilter)
    );

    if (dateOrder === "start_asc") {
      filtered = [...filtered].sort((a, b) => (a.fecha_inicio || "").localeCompare(b.fecha_inicio || ""));
    } else if (dateOrder === "start_desc") {
      filtered = [...filtered].sort((a, b) => (b.fecha_inicio || "").localeCompare(a.fecha_inicio || ""));
    }

    if (deadlineOrder === "deadline_asc") {
      filtered = [...filtered].sort((a, b) => (a.fecha_entrega || "").localeCompare(b.fecha_entrega || ""));
    } else if (deadlineOrder === "deadline_desc") {
      filtered = [...filtered].sort((a, b) => (b.fecha_entrega || "").localeCompare(a.fecha_entrega || ""));
    }

    const userRole = App.user ? App.user.rol : "colaborador";
    const rowsHtml = filtered.map((project) => ProjectRow.render(project, userRole)).join("");
    $("#projects-table tbody").html(rowsHtml);
  },

  applyFilters() {
    this.applyFiltersSilent();
    this.loadProjects();
  },

  renderSummary() {
    const html = SummaryCards.render(App.projects);
    $("#summary-cards").html(html);
  },

  openProject(id) {
    ApiClient.getProject(id)
      .done((project) => {
        const html = ProjectDetail.render(project);
        $("#detail-body").html(html);
        $("#project-detail").removeClass("d-none");
        App.currentProjectId = id;
        this.bindPhaseReorderEvents();
        this.bindMemberManagementEvents();
        this.bindPhaseDetailEvents();
      })
      .fail((xhr) => {
        const msg = xhr.responseJSON?.error || "Erro ao abrir projeto.";
        App.showFeedback(msg, "danger", "#dashboard-feedback");
      });
  },

  showCreateProjectModal() {
    if (this.isSubmitting) return;
    $("#create-project-feedback").empty();
    $("#create-project-form")[0].reset();
    Users.loadUsers();
    bootstrap.Modal.getOrCreateInstance(document.getElementById("create-project-modal")).show();
  },

  handleCreateProject() {
    if (this.isSubmitting) return;

    const responsibleId = parseInt($("#project-responsible").val());
    if (!responsibleId) {
      App.showFeedback("Responsável é obrigatório.", "danger", "#create-project-feedback");
      return;
    }

    const $form = $("#create-project-form");
    const $btn = $form.find("button[type='submit']");
    
    this.isSubmitting = true;
    $btn.prop("disabled", true);

    const selectedMembers = [];
    $(".project-member-checkbox:checked").each(function () {
      const userId = parseInt($(this).val());
      const $select = $(this).closest('.member-row').find('.member-role-select');
      const role = $select.length ? $select.val() : 'Colaborador';
      selectedMembers.push({ usuario_id: userId, rol_especifico: role });
    });

    const initialPhases = [];
    $("#project-phases-inputs-container .dynamic-phase-input input").each(function (index) {
      const phaseName = $(this).val().trim();
      if (phaseName) {
        initialPhases.push({ nombre: phaseName, orden: index + 1 });
      }
    });

    const formData = {
      nombre: $("#project-name").val(),
      descripcion: $("#project-description").val(),
      fecha_inicio: $("#project-start-date").val(),
      fecha_entrega: $("#project-end-date").val(),
      responsable_id: responsibleId,
      members: selectedMembers,
      fases: initialPhases
    };

    ApiClient.createProject(formData)
      .done(() => {
        App.showFeedback("Projeto criado com sucesso!", "success", "#create-project-feedback");
        setTimeout(() => {
          const modal = bootstrap.Modal.getInstance(document.getElementById("create-project-modal"));
          if (modal) modal.hide();
          $form[0].reset();
          $("#project-phases-inputs-container").html(`
            <div class="input-group mb-2 dynamic-phase-input">
              <input type="text" class="form-control form-control-sm" placeholder="Ex: Planejamento" required />
            </div>
          `);
          $("#create-project-feedback").empty();
          this.isSubmitting = false;
          $btn.prop("disabled", false);
          this.loadProjects();
        }, 1000);
      })
      .fail((xhr) => {
        const msg = xhr.responseJSON?.error || "Erro ao criar projeto.";
        App.showFeedback(msg, "danger", "#create-project-feedback");
        this.isSubmitting = false;
        $btn.prop("disabled", false);
      });
  },

  showEditProjectModal(id) {
    const project = App.projects.find((p) => p.id == id);
    if (!project) return;
    $("#edit-project-id").val(project.id);
    $("#edit-project-name").val(project.nombre);
    $("#edit-project-description").val(project.descripcion);
    $("#edit-project-start-date").val(project.fecha_inicio);
    $("#edit-project-end-date").val(project.fecha_entrega);
    
    // Popula select de responsáveis (jefes + admins)
    const validManagers = App.users.filter(
      (u) => u.rol === "jefe_proyecto" || u.rol === "administrador"
    );
    const options = validManagers
      .map((u) => `<option value="${u.id}" ${u.id == project.responsable_id ? 'selected' : ''}>${u.nombre} ${u.apellidos}</option>`)
      .join("");
    $("#edit-project-responsible").html('<option value="">Selecione...</option>' + options);
    
    $("#edit-project-state").val(project.estado);
    $("#edit-project-feedback").empty();
    bootstrap.Modal.getOrCreateInstance(document.getElementById("edit-project-modal")).show();
  },

  handleEditProject() {
    if (this.isSubmitting) return;

    const id = $("#edit-project-id").val();
    const $form = $("#edit-project-form");
    const $btn = $form.find("button[type='submit']");

    this.isSubmitting = true;
    $btn.prop("disabled", true);

    const data = {
      nombre: $("#edit-project-name").val(),
      descripcion: $("#edit-project-description").val(),
      fecha_inicio: $("#edit-project-start-date").val(),
      fecha_entrega: $("#edit-project-end-date").val(),
      responsable_id: parseInt($("#edit-project-responsible").val()),
      estado: $("#edit-project-state").val(),
    };

    ApiClient.updateProject(id, data)
      .done(() => {
        App.showFeedback("Projeto actualizado com sucesso!", "success", "#edit-project-feedback");
        setTimeout(() => {
          const modal = bootstrap.Modal.getInstance(document.getElementById("edit-project-modal"));
          if (modal) modal.hide();
          this.isSubmitting = false;
          $btn.prop("disabled", false);
          this.loadProjects();
          this.openProject(id);
        }, 1000);
      })
      .fail((xhr) => {
        const msg = xhr.responseJSON?.error || "Erro ao atualizar dados.";
        App.showFeedback(msg, "danger", "#edit-project-feedback");
        this.isSubmitting = false;
        $btn.prop("disabled", false);
      });
  },

  handleTogglePauseProject(id, isCurrentlyPaused) {
    ApiClient.updateProject(id, { estado: isCurrentlyPaused ? "en_curso" : "pausado" })
      .done(() => {
        App.showFeedback("Estado do projeto atualizado com sucesso.", "success", "#dashboard-feedback");
        this.loadProjects();
        if (App.currentProjectId == id) this.openProject(id);
      })
      .fail((xhr) => {
        const msg = xhr.responseJSON?.error || "Erro ao alterar estado do projeto.";
        App.showFeedback(msg, "danger", "#dashboard-feedback");
      });
  },

  handleDeleteProject(id) {
    if (!confirm("Tem certeza de que deseja deletar este projeto? Todas as fases e membros serão perdidos.")) return;

    ApiClient.deleteProject(id)
      .done(() => {
        App.showFeedback("Projeto deletado com sucesso.", "success", "#dashboard-feedback");
        $(`tr[data-project-id="${id}"]`).fadeOut(300, () => {
          this.loadProjects();
          if (App.currentProjectId == id) App.closeDetail();
        });
      })
      .fail((xhr) => {
        const msg = xhr.responseJSON?.error || "Falha ao deletar o projeto.";
        App.showFeedback(msg, "danger", "#dashboard-feedback");
      });
  },

  handleCreatePhase() {
    if (!App.currentProjectId) { alert("Nenhum projeto selecionado."); return; }
    if (this.isSubmitting) return;

    const $form = $("#create-phase-form");
    const $btn = $form.find("button[type='submit']");

    this.isSubmitting = true;
    $btn.prop("disabled", true);

    const phaseData = {
      nombre: $("#phase-name").val(),
      descripcion: $("#phase-description").val(),
      orden: parseInt($("#phase-order").val()) || 1,
    };

    ApiClient.createPhase(App.currentProjectId, phaseData)
      .done(() => {
        App.showFeedback("Fase adicionada com sucesso!", "success", "#create-phase-feedback");
        $form[0].reset();
        this.openProject(App.currentProjectId);
        this.loadProjects();
        setTimeout(() => {
          const modal = bootstrap.Modal.getInstance(document.getElementById("create-phase-modal"));
          if (modal) modal.hide();
          $("#create-phase-feedback").empty();
          this.isSubmitting = false;
          $btn.prop("disabled", false);
        }, 1000);
      })
      .fail((xhr) => {
        const msg = xhr.responseJSON?.error || "Erro ao criar fase.";
        App.showFeedback(msg, "danger", "#create-phase-feedback");
        this.isSubmitting = false;
        $btn.prop("disabled", false);
      });
  },

  handleTogglePhase(projectId, phaseId, isChecked, checkbox) {
    checkbox.prop("disabled", true);
    ApiClient.togglePhase(phaseId, isChecked)
      .done(() => {
        this.openProject(projectId);
        this.loadProjects();
      })
      .fail((xhr) => {
        checkbox.prop("checked", !isChecked);
        alert(xhr.responseJSON?.error || "Não foi possível alterar o estado da fase.");
      })
      .always(() => checkbox.prop("disabled", false));
  },

  handleMovePhase(projectId, phaseId, newOrder) {
    const $phaseItem = $(`.phase-item[data-phase-id="${phaseId}"]`);
    const originalOrder = parseInt($phaseItem.find('.badge').text().replace('Ordem: ', ''));
    
    ApiClient.updatePhase(phaseId, { orden: newOrder })
      .done(() => {
        this.openProject(projectId);
        this.loadProjects();
      })
      .fail((xhr) => {
        alert(xhr.responseJSON?.error || "Erro ao reordenar fase.");
        this.openProject(projectId); // restaura visual
      });
  },

  bindPhaseReorderEvents() {
    // Move phase up
    $("#detail-body").off("click", ".move-phase-up").on("click", ".move-phase-up", (e) => {
      const $btn = $(e.currentTarget);
      const phaseId = $btn.data("phase-id");
      const projectId = $btn.data("project-id");
      const $phaseItem = $btn.closest(".phase-item");
      const $prevItem = $phaseItem.prev(".phase-item");
      
      if ($prevItem.length === 0) return;
      
      const prevPhaseId = $prevItem.data("phase-id");
      const prevOrder = parseInt($prevItem.find('.badge').text().replace('Ordem: ', ''));
      const currentOrder = parseInt($phaseItem.find('.badge').text().replace('Ordem: ', ''));
      
      // Swap orders locally first for immediate feedback
      $phaseItem.find('.badge').text(`Ordem: ${prevOrder}`);
      $prevItem.find('.badge').text(`Ordem: ${currentOrder}`);
      
      // Disable buttons during request
      $phaseItem.find("button").prop("disabled", true);
      $prevItem.find("button").prop("disabled", true);
      
      // Update both phases
      ApiClient.updatePhase(phaseId, { orden: prevOrder })
        .done(() => ApiClient.updatePhase(prevPhaseId, { orden: currentOrder }))
        .done(() => {
          this.openProject(projectId);
          this.loadProjects();
        })
        .fail((xhr) => {
          alert(xhr.responseJSON?.error || "Erro ao reordenar fase.");
          this.openProject(projectId);
        });
    });

    // Move phase down
    $("#detail-body").off("click", ".move-phase-down").on("click", ".move-phase-down", (e) => {
      const $btn = $(e.currentTarget);
      const phaseId = $btn.data("phase-id");
      const projectId = $btn.data("project-id");
      const $phaseItem = $btn.closest(".phase-item");
      const $nextItem = $phaseItem.next(".phase-item");
      
      if ($nextItem.length === 0) return;
      
      const nextPhaseId = $nextItem.data("phase-id");
      const nextOrder = parseInt($nextItem.find('.badge').text().replace('Ordem: ', ''));
      const currentOrder = parseInt($phaseItem.find('.badge').text().replace('Ordem: ', ''));
      
      // Swap orders locally first for immediate feedback
      $phaseItem.find('.badge').text(`Ordem: ${nextOrder}`);
      $nextItem.find('.badge').text(`Ordem: ${currentOrder}`);
      
      // Disable buttons during request
      $phaseItem.find("button").prop("disabled", true);
      $nextItem.find("button").prop("disabled", true);
      
      // Update both phases
      ApiClient.updatePhase(phaseId, { orden: nextOrder })
        .done(() => ApiClient.updatePhase(nextPhaseId, { orden: currentOrder }))
        .done(() => {
          this.openProject(projectId);
          this.loadProjects();
        })
        .fail((xhr) => {
          alert(xhr.responseJSON?.error || "Erro ao reordenar fase.");
          this.openProject(projectId);
        });
    });
  },

  bindMemberManagementEvents() {
    $("#detail-body").off("click", ".add-member-btn").on("click", ".add-member-btn", (e) => {
      const $btn = $(e.currentTarget);
      const projectId = $btn.data("project-id");
      const $select = $btn.siblings(".add-member-select");
      const $roleSelect = $btn.siblings(".add-member-role-select");
      const userId = parseInt($select.val());
      const role = $roleSelect.length ? $roleSelect.val() : "Colaborador";

      if (!userId) return;

      ApiClient.addMember(projectId, userId, role)
        .done(() => {
          this.openProject(projectId);
          this.loadProjects();
        })
        .fail((xhr) => {
          alert(xhr.responseJSON?.error || "Erro ao adicionar membro.");
        });
    });

    $("#detail-body").off("click", ".remove-member-btn").on("click", ".remove-member-btn", (e) => {
      const projectId = $(e.currentTarget).data("project-id");
      const userId = $(e.currentTarget).data("user-id");

      if (!confirm("Tem certeza que deseja remover este membro do projeto?")) return;

      ApiClient.removeMember(projectId, userId)
        .done(() => {
          this.openProject(projectId);
          this.loadProjects();
        })
        .fail((xhr) => {
          alert(xhr.responseJSON?.error || "Erro ao remover membro.");
        });
    });
  },

  bindPhaseDetailEvents() {
    $("#detail-body").off("click", ".add-phase-btn").on("click", ".add-phase-btn", (e) => {
      const $btn = $(e.currentTarget);
      const projectId = $btn.data("project-id");
      const $nameInput = $btn.siblings(".new-phase-name-input");
      const $descInput = $btn.siblings(".new-phase-desc-input");
      const nombre = $nameInput.val().trim();

      if (!nombre) {
        alert("Informe o nome da fase.");
        return;
      }

      const nextOrder = ($("#detail-body .phase-item").length + 1);

      const phaseData = {
        nombre,
        descripcion: $descInput.val().trim() || "Fase criada a partir do detalhe do projeto.",
        orden: nextOrder,
      };

      ApiClient.createPhase(projectId, phaseData)
        .done(() => {
          this.openProject(projectId);
          this.loadProjects();
        })
        .fail((xhr) => {
          alert(xhr.responseJSON?.error || "Erro ao adicionar fase.");
        });
    });

    $("#detail-body").off("click", ".remove-phase-btn").on("click", ".remove-phase-btn", (e) => {
      const phaseId = $(e.currentTarget).data("phase-id");
      if (!confirm("Tem certeza que deseja excluir esta fase?")) return;

      ApiClient.deletePhase(phaseId)
        .done(() => {
          this.openProject(App.currentProjectId);
          this.loadProjects();
        })
        .fail((xhr) => {
          alert(xhr.responseJSON?.error || "Erro ao excluir fase.");
        });
    });
  },

  appendPhaseField() {
    const $container = $("#project-phases-inputs-container");
    if ($container.children(".dynamic-phase-input").length >= 15) {
      alert("Limite máximo de 15 fases atingido.");
      return;
    }
    $container.append(`
      <div class="input-group mb-2 dynamic-phase-input">
        <input type="text" class="form-control form-control-sm" placeholder="Ex: Nova Fase" required />
        <button class="btn btn-outline-danger btn-sm remove-phase-field-btn" type="button">×</button>
      </div>
    `);
  }
};