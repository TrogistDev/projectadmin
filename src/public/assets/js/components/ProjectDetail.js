// public/assets/js/components/ProjectDetail.js

const ProjectDetail = {
  render(project) {
    const currentUser = JSON.parse(sessionStorage.getItem('user')) || {};
    
    const canEdit = currentUser.rol === 'administrador' || 
                    (currentUser.rol === 'jefe_proyecto' && project.responsable_id == currentUser.id);

    const canManageTeam = currentUser.rol === 'administrador' || currentUser.rol === 'jefe_proyecto';

const canReorderPhases = (currentUser.rol === 'administrador') || 
                              (currentUser.rol === 'jefe_proyecto' && project.responsable_id == currentUser.id);

    const projectPhases = project.phases || project.fases || [];
    const projectMembers = project.members || project.miembros || project.equipo || [];

    // Fases: nome + descrição + badge ordem, botões ABAIXO alinhados à esquerda
    const phasesHtml = projectPhases.length > 0
      ? projectPhases.map((phase, index) => {
          const isCompleted = phase.completada == 1 || phase.completada === true;
          
          return `
            <div class="list-group-item phase-item" data-phase-id="${phase.id}">
              <div class="d-flex align-items-start">
                <input 
                  type="checkbox" 
                  class="form-check-input me-2 mt-1 toggle-phase-checkbox" 
                  data-project-id="${project.id}" 
                  data-phase-id="${phase.id}" 
                  ${isCompleted ? 'checked' : ''}
                >
                <div class="flex-grow-1">
                  <span class="${isCompleted ? 'text-decoration-line-through text-muted' : ''}">
                    ${phase.nombre}
                  </span>
                  ${phase.descripcion ? `<br><small class="text-muted phase-description">${phase.descripcion}</small>` : ''}
                </div>
              </div>
              <div class="mt-2 ms-6 d-flex flex-wrap align-items-center gap-2">
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

    // Formulário de adicionar fase (linha horizontal)
    const phaseManagementHtml = canEdit ? `
      <div class="mt-3 d-flex flex-column gap-2" style="max-width: 400px;">
        <input type="text" class="form-control form-control-sm new-phase-name-input" placeholder="Nova fase..." />
        <textarea class="form-control form-control-sm new-phase-desc-input" rows="2" placeholder="Descrição (opcional)"></textarea>
        <button class="btn btn-sm btn-outline-primary align-self-start add-phase-btn m-2" data-project-id="${project.id}">
          <i class="fas fa-plus"></i> Adicionar
        </button>
      </div>
    ` : '';

    // Membros: render SEM vírgulas
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
        </li>`).join("")
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
          <p class="text-secondary project-desc-text" id="project-desc-text">${project.descripcion || 'Sem descrição.'}</p>
          <button class="show-more-btn" id="show-more-btn" style="display:none;">Mostrar mais</button>
          <hr>
          <p><strong>Data de Início:</strong> ${Helpers.formatDate(project.fecha_inicio)}</p>
          <p><strong>Previsão de Entrega:</strong> ${Helpers.formatDate(project.fecha_entrega)}</p>
          <p><strong>Responsável:</strong> ${project.responsable_nombre || 'Não definido'}</p>
          
          ${canEdit ? `
            <button class="btn btn-sm btn-warning mt-2 mb-2" id="open-edit-project-modal-btn" data-id="${project.id}">
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
  },
  
  initShowMore() {
    const descEl = document.getElementById('project-desc-text');
    const btn = document.getElementById('show-more-btn');
    if (!descEl || !btn) return;
    
    if (descEl.scrollHeight > descEl.clientHeight) {
      btn.style.display = 'inline-block';
      btn.textContent = 'Mostrar mais';
      btn.onclick = () => {
        if (descEl.classList.contains('expanded')) {
          descEl.classList.remove('expanded');
          btn.textContent = 'Mostrar mais';
        } else {
          descEl.classList.add('expanded');
          btn.textContent = 'Mostrar menos';
        }
      };
    }
  }
};
