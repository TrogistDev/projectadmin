const ModalCreateUser = {
  init() {
    document.getElementById("modals-container").insertAdjacentHTML("beforeend", this.template());
  },

  template() {
    return `
      <div class="modal fade" id="create-user-modal" tabindex="-1">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Cadastrar Novo Usuário</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="create-user-form">
              <div class="modal-body">

                <div class="mb-3">
                  <label for="user-name" class="form-label">Nome</label>
                  <input type="text" class="form-control" id="user-name" required />
                </div>

                <div class="mb-3">
                  <label for="user-lastname" class="form-label">Sobrenome</label>
                  <input type="text" class="form-control" id="user-lastname" required />
                </div>

                <div class="mb-3">
                  <label for="user-email" class="form-label">E-mail</label>
                  <input type="email" class="form-control" id="user-email" required />
                </div>

                <div class="mb-3">
                  <label for="user-password" class="form-label">Senha Provisória</label>
                  <input type="password" class="form-control" id="user-password" required minlength="8" />
                </div>

                <div class="mb-3">
                  <label for="user-role" class="form-label">Função</label>
                  <select class="form-select" id="user-role" required>
                    <option value="colaborador">Colaborador</option>
                    <option value="jefe_proyecto">Chefe de Projeto</option>
                    <option value="administrador">Administrador</option>
                  </select>
                </div>

                <div class="mb-3">
                  <label for="user-department" class="form-label">Departamento (opcional)</label>
                  <input type="text" class="form-control" id="user-department" placeholder="Ex: TI, Desenvolvimento, QA" />
                </div>

                <div id="create-user-feedback"></div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Salvar Usuário</button>
              </div>
            </form>
          </div>
        </div>
      </div>`;
  }
};