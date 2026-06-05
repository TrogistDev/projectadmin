const ModalCreateUser = {
  init() {
    document.getElementById("modals-container").insertAdjacentHTML("beforeend", this.template());
  },

  template() {
    return `
      <div class="modal fade" id="create-user-modal" tabindex="-1">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Gestión de Usuarios</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <ul class="nav nav-tabs" id="user-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                  <button class="nav-link active" id="tab-create-user" data-bs-toggle="tab" data-bs-target="#create-user-tab" type="button">Crear Usuario</button>
                </li>
                <li class="nav-item" role="presentation">
                  <button class="nav-link" id="tab-list-users" data-bs-toggle="tab" data-bs-target="#list-users-tab" type="button">Usuarios</button>
                </li>
              </ul>
              <div class="tab-content pt-3">
                <div class="tab-pane fade show active" id="create-user-tab">
                  <form id="create-user-form">
                    <div class="mb-3">
                      <label for="user-name" class="form-label">Nombre</label>
                      <input type="text" class="form-control" id="user-name" required />
                    </div>
                    <div class="mb-3">
                      <label for="user-lastname" class="form-label">Apellido</label>
                      <input type="text" class="form-control" id="user-lastname" required />
                    </div>
                    <div class="mb-3">
                      <label for="user-email" class="form-label">Correo</label>
                      <input type="email" class="form-control" id="user-email" required />
                    </div>
                    <div class="mb-3">
                      <label for="user-password" class="form-label">Contraseña</label>
                      <input type="password" class="form-control" id="user-password" required minlength="8" />
                    </div>
                    <div class="mb-3">
                      <label for="user-role" class="form-label">Rol</label>
                      <select class="form-select" id="user-role" required>
                        <option value="colaborador">Colaborador</option>
                        <option value="jefe_proyecto">Jefe de Proyecto</option>
                        <option value="administrador">Administrador</option>
                      </select>
                    </div>
                    <div class="mb-3">
                      <label for="user-department" class="form-label">Departamento (opcional)</label>
                      <input type="text" class="form-control" id="user-department" placeholder="Ej: TI, Desarrollo, QA" />
                    </div>
                    <div id="create-user-feedback"></div>
                    <div class="d-flex justify-content-end">
                      <button type="submit" class="btn btn-primary">Guardar Usuario</button>
                    </div>
                  </form>
                </div>
                <div class="tab-pane fade" id="list-users-tab">
                  <div id="users-list-container">
                    <p class="text-muted">Cargando usuarios...</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>`;
  }
};