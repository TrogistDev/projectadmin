const Auth = {
  handleLogin() {
    const email = $("#login-email").val();
    const password = $("#login-password").val();

    $("#login-feedback").html('<div class="alert alert-info">Autenticando...</div>');

    ApiClient.login(email, password)
      .done((response) => {
        console.log("Resposta do servidor no Login:", response);

        if (!response || !response.user) {
          console.error("Erro Estrutural: Objeto 'user' não encontrado na resposta.");
          App.showFeedback("Erro interno: Payload de autenticação inválido.", "danger", "#login-feedback");
          return;
        }

        App.user = {
          id: response.user.id,
          nombre: response.user.nombre || "Usuário",
          rol: response.user.rol || response.user.role || "colaborador"
        };

        sessionStorage.setItem("user", JSON.stringify(App.user));

        try {
          App.showDashboard();
          Projects.loadProjects();
          Users.loadUsers();
        } catch (uiError) {
          console.error("Falha crítica ao renderizar Dashboard:", uiError);
        }
      })
      .fail((xhr) => {
        console.error("Falha na requisição de login:", xhr);
        const msg = xhr.responseJSON?.error || "Erro no login ou credenciais incorretas.";
        App.showFeedback(msg, "danger", "#login-feedback");
      });
  },

  showDashboard() {
    $("#login-screen").addClass("d-none");
    $("#dashboard-screen").removeClass("d-none");
    $("#login-feedback").empty();
    $("#create-project-feedback").empty();

    if (App.user && String(App.user.rol).toLowerCase() === "administrador") {
      $("#admin-users-button").removeClass("d-none");
    } else {
      $("#admin-users-button").addClass("d-none");
    }

    try {
      App.renderHeaderActions();
    } catch (err) {
      console.error("Falha ao renderizar botões de ação:", err);
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

      App.closeDetail();
      App.showLogin();
    });
  },

  showLogin() {
    $("#login-screen").removeClass("d-none");
    $("#dashboard-screen").addClass("d-none");
  }
};