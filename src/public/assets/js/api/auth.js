const Auth = {
  handleLogin() {
    const email = $("#login-email").val();
    const password = $("#login-password").val();

    $("#login-feedback").html('<div class="alert alert-info">Autenticando...</div>');

    ApiClient.login(email, password)
      .done((response) => {
        if (!response || !response.user) {
          App.showFeedback('Erro interno: Payload de autenticação inválido.', "danger", "#login-feedback");
          return;
        }

        App.user = {
          id: response.user.id,
          nombre: response.user.nombre || "Usuário",
          rol: response.user.rol || response.user.role || "colaborador"
        };

        sessionStorage.setItem("user", JSON.stringify(App.user));

        Auth.showDashboard();
        $("#login-feedback").empty();
        Projects.loadProjects();
        Users.loadUsers();
      })
      .fail((xhr) => {
        const msg = xhr.responseJSON?.error || "Erro no login ou credenciais incorretas.";
        App.showFeedback(msg, "danger", "#login-feedback");
      });
  },

  showDashboard() {
    $("#login-screen").addClass("d-none");
    $("#dashboard-screen").removeClass("d-none");
    $("#login-feedback").empty();
    $("#create-project-feedback").empty();
    window.scrollTo(0, 0);

    if (App.user && String(App.user.rol).toLowerCase() === "administrador") {
      $("#admin-users-button").removeClass("d-none");
    } else {
      $("#admin-users-button").addClass("d-none");
    }

    try {
      App.renderHeaderActions();
    } catch (err) {
      App.showFeedback("Erro ao carregar ações do usuário.", "danger", "#dashboard-feedback");
    }

    App.showFeedback(`Bem-vindo(a), ${App.user.nombre}!`, "success", "#dashboard-feedback");
  },

  handleLogout() {
    ApiClient.logout().always(() => {
      App.user = null;
      App.projects = [];
      App.users = [];
      App.currentProjectId = null;
      sessionStorage.removeItem("user");

      $("input[type='text'], input[type='email'], input[type='password'], textarea, select").val("");
      $("#create-project-form")[0].reset();
      $("#edit-project-form")[0].reset();
      $("#create-user-form")[0].reset();
      $("#login-form")[0].reset();

      $("#filter-search").val("");
      $("#filter-state").val("");
      $("#filter-responsible").val("");
      $("#filter-date-order").val("");
      $("#filter-deadline-order").val("");
      $("#filter-start-date").val("");
      $("#filter-end-date").val("");
      $("#filter-badge").addClass("d-none");

      App.closeDetail();
      Auth.showLogin();
    });
  },

  showLogin() {
    $("#login-screen").removeClass("d-none");
    $("#dashboard-screen").addClass("d-none");
    window.scrollTo(0, 0);
  }
};