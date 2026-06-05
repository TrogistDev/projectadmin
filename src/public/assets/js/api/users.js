const Users = {
  isSubmitting: false,

  loadUsers() {
    ApiClient.getUsers()
      .done((users) => {
        App.users = Array.isArray(users) ? users : [];
        this.populateResponsibleSelect();
        this.populateMembersCheckboxes();
        if (typeof Projects.populateResponsibleFilter === 'function') {
          Projects.populateResponsibleFilter();
        }
        if ($("#users-list-container").length) {
          this.renderUsersList();
        }
      })
      .fail(() => {});
  },

  populateResponsibleSelect() {
    const validManagers = App.users.filter(
      (u) => u.rol === "jefe_proyecto" || u.rol === "administrador"
    );
    const options = validManagers
      .map((u) => `<option value="${u.id}">${u.nombre} ${u.apellidos} (${u.rol === "administrador" ? "Admin" : "Jefe"})</option>`)
      .join("");
    $("#project-responsible").html('<option value="">Selecione...</option>' + options);
  },

  populateMembersCheckboxes() {
    const $container = $("#project-members-checkboxes");
    $container.empty();

    if (App.users.length === 0) {
      $container.html('<small class="text-muted">Nenhum usuário disponível.</small>');
      return;
    }

    const roleOptions = ['Colaborador', 'Desarrollador', 'Tester', 'Analista', 'Diseñador', 'Scrum Master'];

    const checkboxesHtml = App.users.map((u) => {
      const safeId = parseInt(u.id);
      const safeName = String(u.nombre).replace(/[/<>]/g, "");
      const safeLastname = String(u.apellidos).replace(/[/<>]/g, "");
      return `
        <div class="member-row">
          <div class="form-check">
            <input class="form-check-input project-member-checkbox" type="checkbox" value="${safeId}" id="member-chk-${safeId}" onchange="Users.onMemberChecked(this)">
            <label class="form-check-label text-truncate" for="member-chk-${safeId}">
              ${safeName} ${safeLastname}
              <span class="badge bg-light text-dark font-monospace ms-1" style="font-size:0.75rem;">${u.rol}</span>
            </label>
          </div>
          <div class="mt-1 member-role-select-wrapper">
            <select class="form-select form-select-sm member-role-select" data-user-id="${safeId}" style="max-width:220px;">
              ${roleOptions.map(r => `<option value="${r}">${r}</option>`).join("")}
            </select>
          </div>
        </div>`;
    }).join("");

    $container.html(checkboxesHtml);
  },

  onMemberChecked(checkbox) {
    const wrapper = checkbox.closest('.member-row').querySelector('.member-role-select-wrapper');
    if (!wrapper) return;
    wrapper.classList.toggle('d-none', !checkbox.checked);
  },

  handleCreateUser() {
    if (this.isSubmitting) return;

    const $form = $("#create-user-form");
    const $btn = $form.find("button[type='submit']");
    
    this.isSubmitting = true;
    $btn.prop("disabled", true);

    const userData = {
      nombre: $("#user-name").val(),
      apellidos: $("#user-lastname").val(),
      correo: $("#user-email").val(),
      contrasena: $("#user-password").val(),
      rol: $("#user-role").val(),
      departamento: $("#user-department").val() || null,
    };

    ApiClient.createUser(userData)
      .done(() => {
        App.showFeedback("Usuario cadastrado com sucesso!", "success", "#create-user-feedback");
        $form[0].reset();
        this.loadUsers();
        setTimeout(() => {
          $("#create-user-feedback").empty();
          this.isSubmitting = false;
          $btn.prop("disabled", false);
        }, 1200);
      })
      .fail((xhr) => {
        const msg = xhr.responseJSON?.error || "Error al crear usuario.";
        App.showFeedback(msg, "danger", "#create-user-feedback");
        this.isSubmitting = false;
        $btn.prop("disabled", false);
      });
  },

  renderUsersList() {
    const users = App.users || [];
    const cardsHtml = users.map(u => `
        <div class="card mb-2 user-card-mobile" data-user-id="${u.id}">
          <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <h6 class="card-title mb-0">${u.nombre} ${u.apellidos}</h6>
              <button class="btn btn-sm btn-outline-danger delete-user-btn" data-user-id="${u.id}">
                <i class="fas fa-trash"></i>
              </button>
            </div>
            <p class="card-text small text-muted mb-2">${u.correo}</p>
            <select class="form-select form-select-sm user-role-select" data-user-id="${u.id}">
              <option value="colaborador" ${u.rol === 'colaborador' ? 'selected' : ''}>Colaborador</option>
              <option value="jefe_proyecto" ${u.rol === 'jefe_proyecto' ? 'selected' : ''}>Jefe de Proyecto</option>
              <option value="administrador" ${u.rol === 'administrador' ? 'selected' : ''}>Administrador</option>
            </select>
            ${u.departamento ? `<p class="card-text small mt-2 mb-0"><strong>Depto:</strong> ${u.departamento}</p>` : ''}
          </div>
        </div>
      `).join("");
    $("#users-list-container").html(cardsHtml || '<p class="text-muted p-2">Nenhum usuario cadastrado.</p>');
  },

  bindUsersEvents() {
    $("#users-list-container").off("change", ".user-role-select").on("change", ".user-role-select", (e) => {
      const $select = $(e.target);
      const userId = $select.data("user-id");
      const newRole = $select.val();

      ApiClient.updateUser(userId, { rol: newRole })
        .done(() => {
          App.showFeedback("Rol actualizado com sucesso!", "success", "#create-user-feedback");
        })
        .fail((xhr) => {
          const msg = xhr.responseJSON?.message || xhr.responseJSON?.error || "Error al actualizar rol.";
          App.showFeedback(msg, "danger", "#create-user-feedback");
        });
    });

    $("#users-list-container").off("click", ".delete-user-btn").on("click", ".delete-user-btn", (e) => {
      const userId = $(e.currentTarget).data("user-id");
      if (!confirm("¿Está seguro de eliminar este usuario?")) return;

      ApiClient.deleteUser(userId)
        .done(() => {
          this.loadUsers();
        })
        .fail((xhr) => {
          alert(xhr.responseJSON?.error || xhr.responseText || "Error al eliminar usuario.");
        });
    });
  }
};