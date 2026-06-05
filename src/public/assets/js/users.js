const Users = {
  isSubmitting: false,

  loadUsers() {
    ApiClient.getUsers()
      .done((users) => {
        App.users = Array.isArray(users) ? users : [];
        this.populateResponsibleSelect();
        this.populateMembersCheckboxes();
      })
      .fail(() => console.warn("Não foi possível carregar os usuários da API"));
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
      const roleOptionsHtml = roleOptions
        .map(r => `<option value="${r}">${r}</option>`)
        .join("");
      return `
        <div class="border-bottom pb-2 mb-2 member-row">
          <div class="form-check">
            <input class="form-check-input project-member-checkbox" type="checkbox" value="${safeId}" id="member-chk-${safeId}" onchange="Users.onMemberChecked(this)">
            <label class="form-check-label text-truncate" for="member-chk-${safeId}" style="max-width:90%;">
              ${safeName} ${safeLastname}
              <span class="badge bg-light text-dark font-monospace" style="font-size:0.75rem;">${u.rol}</span>
            </label>
          </div>
          <div class="ms-4 mt-1 d-none member-role-select-wrapper">
            <select class="form-select form-select-sm member-role-select" data-user-id="${safeId}" style="max-width:220px;">
              ${roleOptionsHtml}
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
        App.showFeedback("Usuário cadastrado com sucesso!", "success", "#create-user-feedback");
        $form[0].reset();
        setTimeout(() => {
          const modal = bootstrap.Modal.getInstance(document.getElementById("create-user-modal"));
          if (modal) modal.hide();
          $("#create-user-feedback").empty();
          this.isSubmitting = false;
          $btn.prop("disabled", false);
          this.loadUsers();
        }, 1200);
      })
      .fail((xhr) => {
        const msg = xhr.responseJSON?.error || "Erro ao criar usuário.";
        App.showFeedback(msg, "danger", "#create-user-feedback");
        this.isSubmitting = false;
        $btn.prop("disabled", false);
      });
  }
};