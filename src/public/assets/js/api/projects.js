// src/public/assets/js/api/projects.js

const Projects = {
  isSubmitting: false,
  currentPage: 1,
  pageSize: 10,
  hasMore: false,
  totalLoaded: 0,
  _lastProjectResponse: null,

  init() {
    this.bindGlobalEvents();
  },

  loadProjects(reset = true) {
    this.updatePageSize();
    if (reset) {
      this.currentPage = 1;
      App.projects = [];
      this.totalLoaded = 0;
    }

    const search = ($("#filter-search").val() || "").trim();
    const estadoFilter = $("#filter-state").val();
    const responsibleFilter = $("#filter-responsible").val();

    const params = {
      page: this.currentPage,
      limit: this.pageSize,
    };

    if (search) params.search = search;
    if (estadoFilter) params.estado = estadoFilter;
    if (responsibleFilter) params.responsable_id = responsibleFilter;

    const dateOrder = $("#filter-date-order").val();
    if (dateOrder) params.date_order = dateOrder;

    const deadlineOrder = $("#filter-deadline-order").val();
    if (deadlineOrder) params.deadline_order = deadlineOrder;

    const startDate = $("#filter-start-date").val();
    const endDate = $("#filter-end-date").val();
    if (startDate || endDate) {
      params.date_start = startDate;
      params.date_end = endDate;
    }

    ApiClient.getProjects(params)
      .done((response) => {
        this._lastProjectResponse = response;
        const pageData = response.data || [];
        const pageNumber = response.page || this.currentPage;
        const total = response.total || pageData.length;

        const parsed = pageData.map((p) => ({
          ...p,
          porcentaje_avance: p.porcentaje_avance !== undefined ? parseInt(p.porcentaje_avance) : 0,
        }));

        App.projects = reset ? parsed : [...App.projects, ...parsed];

        this.totalLoaded = App.projects.length;
        this.hasMore = App.projects.length < total;
        this.currentPage = pageNumber + 1;

        this.renderProjects();
        this.renderSummary();
        this.renderLoadMoreButton();
        this.populateResponsibleFilter();
      })
      .fail((xhr) => {
        const msg = xhr.responseJSON?.error || "Erro ao carregar projetos.";
        App.showFeedback(msg, "danger", "#dashboard-feedback");
      });
  },

  updatePageSize() {
    this.pageSize = window.innerWidth < 768 ? 5 : 10;
  },

  renderLoadMoreButton() {
    const $btn = $("#load-more-projects-btn");
    if (!this.hasMore) {
      if ($btn.length) $btn.remove();
      return;
    }
    if (!$btn.length) {
      $("<button>")
        .attr("id", "load-more-projects-btn")
        .addClass("btn btn-outline-primary mt-3")
        .text("Mostrar mais projetos")
        .on("click", () => this.loadProjects(false))
        .insertAfter("#projects-table");
    }
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

    let filtered = App.projects.filter(
      (p) => p.nombre.toLowerCase().includes(search) &&
             (!estadoFilter || p.estado === estadoFilter) &&
             (!responsibleFilter || p.responsable_id == responsibleFilter)
    );

    if (dateOrder === "start_asc") {
      filtered.sort((a, b) => (a.fecha_inicio || "").localeCompare(b.fecha_inicio || ""));
    } else if (dateOrder === "start_desc") {
      filtered.sort((a, b) => (b.fecha_inicio || "").localeCompare(a.fecha_inicio || ""));
    }

    const userRole = App.user ? App.user.rol : "colaborador";
    const rowsHtml = filtered.map((project) => ProjectRow.render(project, userRole)).join("");
    $("#projects-table tbody").html(rowsHtml);

    this.updateFilterBadge();
  },

  applyFilters() {
    this.loadProjects(true);
    this.updateFilterBadge();
  },

  updateFilterBadge() {
    const conditions = [
      $("#filter-search").val()?.trim(),
      $("#filter-state").val(),
      $("#filter-responsible").val(),
      $("#filter-date-order").val(),
      $("#filter-deadline-order").val(),
      $("#filter-start-date").val() || $("#filter-end-date").val()
    ];
    
    const count = conditions.filter(Boolean).length;
    const $badge = $("#filter-badge");
    count > 0 ? $badge.text(count).removeClass("d-none") : $badge.addClass("d-none");
  },

  renderSummary() {
    $("#summary-cards").html(SummaryCards.render(this._lastProjectResponse));
  },

  openProject(id) {
    ApiClient.getProject(id)
      .done((project) => {
        $("#detail-title").text(project.nombre || 'Detalhe do projeto');
        $("#detail-body").html(ProjectDetail.render(project));
        $("#project-detail").removeClass("d-none");
        App.currentProjectId = id;
        
        if (typeof ProjectDetail.initShowMore === 'function') {
          ProjectDetail.initShowMore();
        }
        setTimeout(() => {
          $('html, body').animate({ scrollTop: $("#project-detail").offset().top - 20 }, 400);
        }, 100);
      })
      .fail((xhr) => {
        App.showFeedback(xhr.responseJSON?.error || "Erro ao abrir projeto.", "danger", "#dashboard-feedback");
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
      selectedMembers.push({ usuario_id: userId, rol_especifico: $select.length ? $select.val() : 'Colaborador' });
    });

    const initialPhases = [];
    $("#project-phases-inputs-container .dynamic-phase-input").each(function (index) {
      const phaseName = $(this).find(".phase-name-input, input[type='text']").first().val().trim();
      if (phaseName) {
        initialPhases.push({ 
          nombre: phaseName, 
          descripcion: $(this).find(".phase-desc-input").val().trim(),
          orden: index + 1 
        });
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
              <input type="text" class="form-control form-control-sm phase-name-input" placeholder="Ex: Planejamento" required />
              <textarea class="form-control form-control-sm phase-desc-input" rows="2" placeholder="Descrição da fase (opcional)"></textarea>
              <button class="btn btn-outline-danger btn-sm remove-phase-field-btn" type="button">×</button>
            </div>
          `);
          $("#create-project-feedback").empty();
          this.isSubmitting = false;
          $btn.prop("disabled", false);
        }, 1000);
      })
      .fail((xhr) => {
        App.showFeedback(xhr.responseJSON?.error || "Erro ao criar projeto.", "danger", "#create-project-feedback");
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
    
    const validManagers = App.users.filter((u) => u.rol === "jefe_proyecto" || u.rol === "administrador");
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
          this.openProject(id);
        }, 1000);
      })
      .fail((xhr) => {
        App.showFeedback(xhr.responseJSON?.error || "Erro ao atualizar dados.", "danger", "#edit-project-feedback");
        this.isSubmitting = false;
        $btn.prop("disabled", false);
      });
  },

  handleTogglePauseProject(id, isCurrentlyPaused) {
    const novoEstado = isCurrentlyPaused ? "en_curso" : "pausado";

    ApiClient.updateProject(id, { estado: novoEstado })
      .done(() => {
        App.showFeedback("Estado do projeto atualizado com sucesso.", "success", "#dashboard-feedback");
        if (App.projects && App.projects.length > 0) {
          App.projects = App.projects.map((p) => {
            if (p.id == id) {
              return { ...p, estado: novoEstado };
            }
            return p;
          });
        }
        this.renderProjects();
        this.renderSummary();

        if (App.currentProjectId == id) {
          this.openProject(id); 
        }

      })
      .fail((xhr) => {
        App.showFeedback(xhr.responseJSON?.error || "Erro ao alterar estado.", "danger", "#dashboard-feedback");
      });
  },

  handleDeleteProject(id) {
    if (!confirm("Tem certeza de que deseja deletar este projeto? Todas as fases e membros serão perdidos.")) return;

    ApiClient.deleteProject(id)
      .done(() => {
        App.showFeedback("Projeto deletado com sucesso.", "success", "#dashboard-feedback");
        $(`tr[data-project-id="${id}"]`).fadeOut(300, () => {
          if (App.currentProjectId == id) App.closeDetail();
        });
      })
      .fail((xhr) => {
        App.showFeedback(xhr.responseJSON?.error || "Falha ao deletar o projeto.", "danger", "#dashboard-feedback");
      });
  },

  bindGlobalEvents() {
    const self = this;


    $("#projects-table").off("click");
    $("#detail-body").off("click");
    $(document).off("click", ".remove-phase-field-btn");

    $("#projects-table")
      .on("click", ".view-project-btn", function() { self.openProject($(this).data("id")); })
      .on("click", ".pause-project-btn", function(e) { 
        e.stopPropagation();
        self.handleTogglePauseProject($(this).data("id"), $(this).data("paused")); 
      })
      .on("click", ".delete-project-btn", function() { self.handleDeleteProject($(this).data("id")); });
    $("#detail-body")
      .on("click", "#open-edit-project-modal-btn", function() { self.showEditProjectModal($(this).data("id")); })
      .on("change", ".toggle-phase-checkbox", function() { ProjectPhases.handleToggle($(this).data("project-id"), $(this).data("phase-id"), this.checked, $(this)); })
      .on("click", ".move-phase-up", function() { ProjectPhases.handleOrderChange($(this).data("phase-id"), $(this).data("project-id"), "up"); })
      .on("click", ".move-phase-down", function() { ProjectPhases.handleOrderChange($(this).data("phase-id"), $(this).data("project-id"), "down"); })
      .on("click", ".add-phase-btn", function() { ProjectPhases.handleCreateInline($(this)); })
      .on("click", ".remove-phase-btn", function() { ProjectPhases.handleDelete($(this)); })
      .on("click", ".add-member-btn", function() { ProjectMembers.handleAdd($(this)); })
      .on("click", ".remove-member-btn", function() { ProjectMembers.handleRemove($(this)); });

    $(document).on("click", ".remove-phase-field-btn", function() { $(this).closest(".dynamic-phase-input").remove(); });
  }
};

$(document).ready(() => Projects.init());