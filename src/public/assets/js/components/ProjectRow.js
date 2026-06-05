const ProjectRow = {
  truncate(text, max = 30) {
    if (!text) return '';
    return text.length > max ? text.slice(0, max) + '...' : text;
  },

  render(project, currentUserRole, helpers = Helpers) {
    const inicio = helpers.formatDate(project.fecha_inicio);
    const entrega = helpers.formatDate(project.fecha_entrega);
    const progresso = project.porcentaje_avance || 0;

    let progressColorClass = "bg-primary";
    switch (project.estado) {
      case "planificacion":
        progressColorClass = "bg-secondary";
        break;
      case "pausado":
        progressColorClass = "bg-warning text-dark";
        break;
      case "finalizado":
        progressColorClass = "bg-success";
        break;
    }

    let actionButtonsHtml = "";
    if (currentUserRole === "administrador") {
      const isPaused = project.estado === "pausado";
      const pauseBtnText = isPaused ? "Retomar" : "Pausar";
      const pauseBtnClass = isPaused ? "btn-outline-success" : "btn-outline-warning";

      actionButtonsHtml = `
        <button class="btn btn-sm ${pauseBtnClass} pause-project-btn" data-id="${project.id}" data-paused="${isPaused}">
          ${pauseBtnText}
        </button>
        <button class="btn btn-sm btn-outline-danger delete-project-btn" data-id="${project.id}">
          Deletar
        </button>
      `;
    } else if (currentUserRole === "jefe_proyecto") {
      const isPaused = project.estado === "pausado";
      const pauseBtnText = isPaused ? "Retomar" : "Pausar";
      const pauseBtnClass = isPaused ? "btn-outline-success" : "btn-outline-warning";

      actionButtonsHtml = `
        <button class="btn btn-sm ${pauseBtnClass} pause-project-btn" data-id="${project.id}" data-paused="${isPaused}">
          ${pauseBtnText}
        </button>
      `;
    }

    return `
      <tr data-project-id="${project.id}">
        <td data-label="Projeto"><strong>${project.nombre}</strong></td>
        <td data-label="Descrição">${this.truncate(project.descripcion, 30)}</td>
        <td data-label="Início">${inicio}</td>
        <td data-label="Entrega">${entrega}</td>
        <td data-label="Responsável">${project.responsable_nombre || "Não atribuído"}</td>
        <td data-label="Estado"><span class="badge ${project.estado === 'finalizado' ? 'bg-success' : 'bg-light text-dark border'}">${helpers.statusLabel(project.estado)}</span></td>
        <td data-label="Progresso">
          <div class="progress project-row-progress progress-full-mobile" style="height: 18px; background-color: #dee2e6;">
            <div class="progress-bar ${progressColorClass}" role="progressbar" style="width: ${progresso}%;" aria-valuenow="${progresso}" aria-valuemin="0" aria-valuemax="100">
              ${progresso}%
            </div>
          </div>
        </td>
        <td data-label="Ações">
          <div class="btn-group gap-1">
            <button class="btn btn-sm btn-outline-primary view-project-btn" data-id="${project.id}">Ver</button>
            ${actionButtonsHtml}
          </div>
        </td>
      </tr>`;
  }
};
