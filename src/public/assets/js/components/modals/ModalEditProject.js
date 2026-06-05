const ModalEditProject = {
  init() {
    document.getElementById("modals-container").insertAdjacentHTML("beforeend", this.template());
  },

  template() {
    return `
      <div class="modal fade" id="edit-project-modal" tabindex="-1">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Editar Projeto</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="edit-project-form">
              <input type="hidden" id="edit-project-id" />
              <div class="modal-body">

                <div class="mb-3">
                  <label for="edit-project-name" class="form-label">Nome do projeto</label>
                  <input type="text" class="form-control" id="edit-project-name" required />
                </div>

                <div class="mb-3">
                  <label for="edit-project-description" class="form-label">Descrição</label>
                  <textarea class="form-control" id="edit-project-description" rows="3" required></textarea>
                </div>

                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label for="edit-project-start-date" class="form-label">Data de Início</label>
                    <input type="date" class="form-control" id="edit-project-start-date" required />
                  </div>
                  <div class="col-md-6 mb-3">
                    <label for="edit-project-end-date" class="form-label">Previsão de Entrega</label>
                    <input type="date" class="form-control" id="edit-project-end-date" required />
                  </div>
                </div>

                <div class="mb-3">
                  <label for="edit-project-responsible" class="form-label">Responsável (Jefe de Projeto)</label>
                  <select class="form-select" id="edit-project-responsible" required></select>
                </div>

                <div class="mb-3">
                  <label for="edit-project-state" class="form-label">Estado do Projeto</label>
                  <select class="form-select" id="edit-project-state" required>
                    <option value="planificacion">Planejamento</option>
                    <option value="en_curso">Em curso</option>
                    <option value="pausado">Pausado</option>
                    <option value="finalizado">Finalizado</option>
                  </select>
                </div>

                <div id="edit-project-feedback"></div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Salvar Alterações</button>
              </div>
            </form>
          </div>
        </div>
      </div>`;
  }
};