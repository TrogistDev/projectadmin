const ProjectRow = {
  render(project, currentUserRole, helpers = Helpers) {
    const inicio = helpers.formatDate(project.fecha_inicio);
    const entrega = helpers.formatDate(project.fecha_entrega);
    const progresso = project.porcentaje_avance || 0;

    // Mapeamento rígido de classes CSS com base no estado real vindo do banco
    let progressColorClass = "bg-primary"; // padrão azul para en_curso
    
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

    // Regras rígidas de renderização de controle por nível de permissão (Administrador / Jefe de Proyecto)
    let actionButtonsHtml = "";
    if (currentUserRole === "administrador" || currentUserRole === "jefe_proyecto") {
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
    }

    return `
      <tr data-project-id="${project.id}">
        <td><strong>${project.nombre}</strong></td>
        <td>${inicio}</td>
        <td>${entrega}</td>
        <td>${project.responsable_nombre || "Não atribuído"}</td>
        <td><span class="badge ${project.estado === 'finalizado' ? 'bg-success' : 'bg-light text-dark border'}">${helpers.statusLabel(project.estado)}</span></td>
        <td>
          <div class="progress" style="height: 18px;">
            <div 
              class="progress-bar ${progressColorClass}" 
              role="progressbar" 
              style="width: ${progresso}%;" 
              aria-valuenow="${progresso}" 
              aria-valuemin="0" 
              aria-valuemax="100"
            >
              ${progresso}%
            </div>
          </div>
        </td>
        <td>
          <div class="btn-group gap-1">
            <button class="btn btn-sm btn-outline-primary view-project-btn" data-id="${project.id}">Ver</button>
            ${actionButtonsHtml}
          </div>
        </td>
      </tr>`;
  }
};