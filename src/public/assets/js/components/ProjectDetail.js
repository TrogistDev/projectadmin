// public/assets/js/components/ProjectDetail.js

const ProjectDetail = {
  render(project) {
    const currentUser = JSON.parse(sessionStorage.getItem('user')) || {};
    
    // Verifica se o usuário logado tem direito de editar o projeto (Admin ou o próprio Chefe Responsável)
    const canEdit = currentUser.rol === 'administrador' || 
                    (currentUser.rol === 'jefe_proyecto' && project.responsable_id == currentUser.id);

    // Verifica se pode reordenar fases (Admin, Jefe do projeto, ou Colaborador membro)
    const canReorderPhases = (currentUser.rol === 'administrador') || 
                             (currentUser.rol === 'jefe_proyecto' && project.responsable_id == currentUser.id) || 
                             (currentUser.rol === 'colaborador' && (project.members || project.miembros || []).some(m => m.usuario_id == currentUser.id));

    // CORREÇÃO CRÍTICA: Mapeamento defensivo aceitando 'phases' ou 'fases'
    const projectPhases = project.phases || project.fases || [];
    
    // CORREÇÃO CRÍTICA: Mapeamento defensivo aceitando 'members' ou 'miembros' ou 'equipo'
    const projectMembers = project.members || project.miembros || project.equipo || [];

    // Render das Fases com checkboxes controlados e botões de reordenação
    const phasesHtml = projectPhases.length > 0
      ? projectPhases.map((phase, index) => {
          const isCompleted = phase.completada == 1 || phase.completada === true;
          
          return `
            <div class="list-group-item d-flex justify-content-between align-items-center phase-item" data-phase-id="${phase.id}">
              <div class="d-flex align-items-center">
                <input 
                  type="checkbox" 
                  class="form-check-input me-2 toggle-phase-checkbox" 
                  data-project-id="${project.id}" 
                  data-phase-id="${phase.id}" 
                  ${isCompleted ? 'checked' : ''}
                >
                <span class="${isCompleted ? 'text-decoration-line-through text-muted' : ''}">
                  ${phase.nombre}
                </span>
              </div>
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-light text-dark border">Ordem: ${phase.orden}</span>
                ${canReorderPhases ? `
                  <button class="btn btn-sm btn-outline-secondary move-phase-up" data-phase-id="${phase.id}" data-project-id="${project.id}" ${index === 0 ? 'disabled' : ''} title="Mover para cima">
                    <i class="fas fa-arrow-up"></i>
                  </button>
                  <button class="btn btn-sm btn-outline-secondary move-phase-down" data-phase-id="${phase.id}" data-project-id="${project.id}" ${index === projectPhases.length - 1 ? 'disabled' : ''} title="Mover para baixo">
                    <i class="fas fa-arrow-down"></i>
                  </button>
                ` : ''}
                ${canEdit ? `
                  <button class="btn btn-sm btn-outline-danger remove-phase-btn" data-project-id="${project.id}" data-phase-id="${phase.id}" title="Excluir fase">
                    <i class="fas fa-trash"></i>
                  </button>
                ` : ''}
              </div>
            </div>
          `;
        }).join("")
      : `<p class="text-muted p-2 m-0">Nenhuma fase cadastrada neste projeto.</p>`;

    const phaseManagementHtml = canEdit ? `
      <div class="mt-2 d-flex gap-2 align-items-center">
        <input type="text" class="form-control form-control-sm new-phase-name-input" placeholder="Nova fase..." style="max-width:220px;" />
        <input type="text" class="form-control form-control-sm new-phase-desc-input" placeholder="Descrição (opcional)" style="max-width:260px;" />
        <button class="btn btn-sm btn-outline-primary add-phase-btn" data-project-id="${project.id}">
          <i class="fas fa-plus"></i> Adicionar
        </button>
      </div>
    ` : '';

    // Render dos Membros da Equipe
    const canManageTeam = currentUser.rol === 'administrador' || currentUser.rol === 'jefe_proyecto';

    const membersHtml = projectMembers.length > 0
      ? projectMembers.map(m => `
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <div>
            <strong>${m.nombre} ${m.apellidos}</strong> <br>
            <small class="text-muted">${m.correo}</small>
          </div>
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-info">${m.rol_especifico || 'Membro'}</span>
            ${canManageTeam ? `<button class="btn btn-sm btn-outline-danger remove-member-btn" data-project-id="${project.id}" data-user-id="${m.usuario_id}" title="Remover membro"><i class="fas fa-times"></i></button>` : ''}
          </div>
        </li>
      `)
      : `<p class="text-muted p-2 m-0">Nenhum membro alocado.</p>`;

    const allUsers = App.users || [];
    const memberUserIds = (projectMembers || []).map(m => parseInt(m.usuario_id));
    const availableUsers = allUsers.filter(u => !memberUserIds.includes(parseInt(u.id)) && parseInt(u.id) !== parseInt(project.responsable_id));
    const availableUsersOptions = availableUsers.map(u => `<option value="${u.id}">${u.nombre} ${u.apellidos} (${u.rol})</option>`).join("");

    const memberManagementHtml = canManageTeam ? `
      <div class="mt-2 d-flex gap-2 align-items-center">
        <select class="form-select form-select-sm add-member-select" data-project-id="${project.id}" style="max-width:260px;">
          <option value="">Adicionar membro...</option>
          ${availableUsersOptions}
        </select>
        <select class="form-select form-select-sm add-member-role-select" data-project-id="${project.id}" style="max-width:180px;">
          <option value="Colaborador">Colaborador</option>
          <option value="Desarrollador">Desarrollador</option>
          <option value="Tester">Tester</option>
          <option value="Analista">Analista</option>
          <option value="Diseñador">Diseñador</option>
          <option value="Scrum Master">Scrum Master</option>
        </select>
        <button class="btn btn-sm btn-outline-primary add-member-btn" data-project-id="${project.id}">
          <i class="fas fa-plus"></i>
        </button>
      </div>
    ` : '';

    return `
      <div class="row">
        <div class="col-md-6">
          <h6><strong>Descrição:</strong></h6>
          <p class="text-secondary">${project.descripcion || 'Sem descrição.'}</p>
          <hr>
          <p><strong>Data de Início:</strong> ${Helpers.formatDate(project.fecha_inicio)}</p>
          <p><strong>Previsão de Entrega:</strong> ${Helpers.formatDate(project.fecha_entrega)}</p>
          <p><strong>Responsável:</strong> ${project.responsable_nombre || 'Não definido'}</p>
          
          ${canEdit ? `
            <button class="btn btn-sm btn-warning mt-2" id="open-edit-project-modal-btn" data-id="${project.id}">
              <i class="fas fa-edit"></i> Editar Dados do Projeto
            </button>
          ` : ''}
        </div>
        
        <div class="col-md-6">
          <div class="card mb-3">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
              <h6 class="mb-0">Fases do Projeto</h6>
              <span class="badge bg-success">${project.porcentaje_avance || 0}% Concluído</span>
            </div>
            <div class="list-group list-group-flush">
              ${phasesHtml}
            </div>
            ${phaseManagementHtml}
          </div>

          <div class="card">
            <div class="card-header bg-light">
              <h6 class="mb-0">Membros da Equipe</h6>
            </div>
            <ul class="list-group list-group-flush">
              ${membersHtml}
            </ul>
            ${memberManagementHtml}
          </div>
        </div>
      </div>
    `;
  }
};