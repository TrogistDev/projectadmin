const ModalCreateProject = {
  init() {
    document.getElementById("modals-container").insertAdjacentHTML("beforeend", this.template());
  },

  template() {
    return `
      <div class="modal fade" id="create-project-modal" tabindex="-1">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Novo Projeto</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="create-project-form">
              <div class="modal-body">

                <div class="mb-3">
                  <label for="project-name" class="form-label">Nome do projeto</label>
                  <input type="text" class="form-control" id="project-name" required />
                </div>

                <div class="mb-3">
                  <label for="project-description" class="form-label">Descrição</label>
                  <textarea class="form-control" id="project-description" rows="3" required></textarea>
                </div>

                <div class="mb-3">
                  <label for="project-start-date" class="form-label">Data de início</label>
                  <input type="date" class="form-control" id="project-start-date" required />
                </div>

                <div class="mb-3">
                  <label for="project-end-date" class="form-label">Data de entrega</label>
                  <input type="date" class="form-control" id="project-end-date" required />
                </div>

                <div class="mb-3">
                  <label for="project-responsible" class="form-label">Responsável</label>
                  <select class="form-select" id="project-responsible" required>
                    <option value="">Selecione...</option>
                  </select>
                </div>

                <div class="mb-3">
                  <label class="form-label">Participantes Iniciais</label>
                  <div id="project-members-checkboxes" class="border rounded p-2" style="max-height:150px; overflow-y:auto">
                    <small class="text-muted">Carregando usuários disponíveis...</small>
                  </div>
                </div>

                <div class="mb-3">
                  <label class="form-label d-flex justify-content-between align-items-center">
                    Fases Iniciais
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="add-phase-field-btn">
                      + Adicionar Fase
                    </button>
                  </label>
                  <div id="project-phases-inputs-container">
                    <div class="input-group mb-2 dynamic-phase-input">
                      <input type="text" class="form-control form-control-sm" placeholder="Ex: Planejamento" required />
                    </div>
                  </div>
                </div>

                <div id="create-project-feedback"></div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Criar Projeto</button>
              </div>
            </form>
          </div>
        </div>
      </div>`;
  }
};