const App = {
  user: null,
  projects: [],
  users: [],
  currentProjectId: null,

  init() {
    ModalCreateProject.init();
    ModalEditProject.init();
    ModalCreateUser.init();
    this.bindEvents();

    const savedUser = sessionStorage.getItem('user');
    if (savedUser) {
      try {
        this.user = JSON.parse(savedUser);
        Auth.showDashboard();
        Projects.updatePageSize();
        Projects.loadProjects();
        Users.loadUsers();
        return;
      } catch (e) {
        sessionStorage.removeItem('user');
      }
    }
    Auth.showLogin();
  },

  bindEvents() {
    $(window).on("resize", () => {
      Projects.updatePageSize();
    });
    $("#login-form").on("submit", (e) => { e.preventDefault(); Auth.handleLogin(); });
    $("#logout-button").on("click", () => Auth.handleLogout());

    $("#create-project-form").on("submit", (e) => { e.preventDefault(); Projects.handleCreateProject(); });
    $(document).on("click", "#add-phase-field-btn", (e) => { e.preventDefault(); Projects.appendPhaseField(); });
    $("#project-phases-inputs-container").on("click", ".remove-phase-field-btn", (e) => { e.preventDefault(); $(e.target).closest(".dynamic-phase-input").remove(); });
    
    $(document).on("submit", "#create-phase-form", (e) => { 
      e.preventDefault(); 
      Projects.handleCreatePhase(); 
    });

    $("#admin-users-button").on("click", () => {
      bootstrap.Modal.getOrCreateInstance(document.getElementById("create-user-modal")).show();
      Users.loadUsers();
      Users.renderUsersList();
      Users.bindUsersEvents();
    });
    $("#create-user-form").on("submit", (e) => { e.preventDefault(); Users.handleCreateUser(); });

    $("#dashboard-screen").on("click", ".view-project-btn", (event) => {
      event.preventDefault();
      event.stopPropagation();
      const id = $(event.target).data("id");
      if (id) {
        Projects.openProject(id);
        setTimeout(() => {
          const $detail = $("#project-detail");
          if ($detail.length && !$detail.hasClass("d-none")) {
            $('html, body').animate({
              scrollTop: $detail.offset().top - 20
            }, 400);
          }
        }, 150);
      }
    });

    $("#dashboard-screen").on("click", "#create-project-button", () => {
      Projects.showCreateProjectModal();
    });

    $("#close-detail").on("click", () => this.closeDetail());

    $("#detail-body").on("click", "#open-edit-project-modal-btn", (event) => {
      const id = $(event.target).data("id");
      Projects.showEditProjectModal(id);
    });

    $("#edit-project-form").on("submit", (e) => { e.preventDefault(); Projects.handleEditProject(); });

    $("#detail-body").on("change", ".toggle-phase-checkbox", (event) => {
      const $checkbox = $(event.target);
      const phaseId = $checkbox.data("phase-id");
      const projectId = $checkbox.data("project-id");
      const isChecked = $checkbox.is(":checked");
      Projects.handleTogglePhase(projectId, phaseId, isChecked, $checkbox);
    });

    $("#dashboard-screen").on("click", ".pause-project-btn", (event) => {
      event.stopPropagation();
      const id = $(event.target).data("id");
      const isCurrentlyPaused = $(event.target).data("paused");
      Projects.handleTogglePauseProject(id, isCurrentlyPaused);
    });

    $("#dashboard-screen").on("click", ".delete-project-btn", (event) => {
      event.stopPropagation();
      const id = $(event.target).data("id");
      Projects.handleDeleteProject(id);
    });

    $("#open-filter-modal-btn").on("click", () => {
      if (typeof Projects.populateResponsibleFilter === 'function') {
        Projects.populateResponsibleFilter();
      }
      const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("filter-modal"));
      modal.show();
    });

    $("#apply-filters-btn").on("click", () => {
      Projects.applyFilters();
      bootstrap.Modal.getInstance(document.getElementById("filter-modal")).hide();
    });
  },

  closeDetail() {
    this.currentProjectId = null;
    $("#project-detail").addClass("d-none");
  },

  renderHeaderActions() {
    const role = App.user ? App.user.rol : "colaborador";
    const $buttonContainer = $("#create-project-button-container");
    if (role === "administrador" || role === "jefe_proyecto") {
      $buttonContainer.html('<button class="btn btn-primary" id="create-project-button">Novo projeto</button>');
    }
  },

  showFeedback(message, type = "info", target = "#login-feedback") {
    $(target).html(`
      <div class="alert alert-${type} alert-dismissible fade show" role="alert">
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>`);
  }
};

$(document).ready(() => App.init());