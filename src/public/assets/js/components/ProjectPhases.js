// src/public/assets/js/components/ProjectPhases.js

const ProjectPhases = {
  appendField() {
    const $container = $("#project-phases-inputs-container");
    if ($container.children(".dynamic-phase-input").length >= 15) {
      alert("Limite máximo de 15 fases atingido.");
      return;
    }
    $container.append(`
      <div class="input-group mb-2 dynamic-phase-input">
        <input type="text" class="form-control form-control-sm phase-name-input" placeholder="Ex: Nova Fase" required />
        <textarea class="form-control form-control-sm phase-desc-input" rows="2" placeholder="Descrição (opcional)"></textarea>
        <button class="btn btn-outline-danger btn-sm remove-phase-field-btn" type="button">×</button>
      </div>
    `);
  },

  handleToggle(projectId, phaseId, isChecked, $checkbox) {
    $checkbox.prop("disabled", true);
    ApiClient.togglePhase(phaseId, isChecked)
      .done(() => Projects.openProject(projectId))
      .fail((xhr) => {
        $checkbox.prop("checked", !isChecked);
        alert(xhr.responseJSON?.error || "Não foi possível alterar o estado da fase.");
      })
      .always(() => $checkbox.prop("disabled", false));
  },

  handleOrderChange(phaseId, projectId, direction) {
    const $phaseItem = $(`.phase-item[data-phase-id="${phaseId}"]`);
    const $siblingItem = direction === "up" ? $phaseItem.prev(".phase-item") : $phaseItem.next(".phase-item");

    if ($siblingItem.length === 0) return;

    const siblingPhaseId = $siblingItem.data("phase-id");
    const currentOrder = parseInt($phaseItem.find('.badge').text().replace('Ordem: ', ''));
    const siblingOrder = parseInt($siblingItem.find('.badge').text().replace('Ordem: ', ''));

    // Swap visual imediato (UX Otimista)
    $phaseItem.find('.badge').text(`Ordem: ${siblingOrder}`);
    $siblingItem.find('.badge').text(`Ordem: ${currentOrder}`);
    $phaseItem.find("button").prop("disabled", true);
    $siblingItem.find("button").prop("disabled", true);

    ApiClient.updatePhase(phaseId, { orden: siblingOrder })
      .done(() => ApiClient.updatePhase(siblingPhaseId, { orden: currentOrder }))
      .done(() => Projects.openProject(projectId))
      .fail((xhr) => {
        alert(xhr.responseJSON?.error || "Erro ao reordenar fase.");
        Projects.openProject(projectId);
      });
  },

  handleCreate() {
    if (!App.currentProjectId) return;
    if (Projects.isSubmitting) return;

    const $form = $("#create-phase-form");
    const $btn = $form.find("button[type='submit']");
    const nombre = $("#phase-name").val().trim();

    if (!nombre) {
      alert("Informe o nome da fase.");
      return;
    }

    Projects.isSubmitting = true;
    $btn.prop("disabled", true);

    const phaseData = {
      nombre,
      descripcion: $("#phase-description").val().trim(),
      orden: parseInt($("#phase-order").val()) || 1,
    };

    ApiClient.createPhase(App.currentProjectId, phaseData)
      .done(() => {
        App.showFeedback("Fase adicionada com sucesso!", "success", "#create-phase-feedback");
        $form[0].reset();
        Projects.openProject(App.currentProjectId);
        setTimeout(() => {
          const modal = bootstrap.Modal.getInstance(document.getElementById("create-phase-modal"));
          if (modal) modal.hide();
          $("#create-phase-feedback").empty();
          Projects.isSubmitting = false;
          $btn.prop("disabled", false);
        }, 1000);
      })
      .fail((xhr) => {
        App.showFeedback(xhr.responseJSON?.error || "Erro ao criar fase.", "danger", "#create-phase-feedback");
        Projects.isSubmitting = false;
        $btn.prop("disabled", false);
      });
  },

  handleCreateInline($btn) {
    const projectId = $btn.data("project-id");
    const nombre = $btn.siblings(".new-phase-name-input").val().trim();
    const descripcion = $btn.siblings(".new-phase-desc-input").val().trim();

    if (!nombre) {
      alert("Informe o nome da fase.");
      return;
    }

    const nextOrder = $("#detail-body .phase-item").length + 1;
    const phaseData = {
      nombre,
      descripcion: descripcion || "Fase criada a partir do detalhe do projeto.",
      orden: nextOrder,
    };

    ApiClient.createPhase(projectId, phaseData)
      .done(() => Projects.openProject(projectId))
      .fail((xhr) => alert(xhr.responseJSON?.error || "Erro ao adicionar fase."));
  },

  handleDelete($btn) {
    const phaseId = $btn.data("phase-id");
    if (!confirm("Tem certeza que deseja excluir esta fase?")) return;

    ApiClient.deletePhase(phaseId)
      .done(() => Projects.openProject(App.currentProjectId))
      .fail((xhr) => alert(xhr.responseJSON?.error || "Erro ao excluir fase."));
  }
};