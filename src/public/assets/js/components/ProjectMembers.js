// src/public/assets/js/components/ProjectMembers.js

const ProjectMembers = {
  handleAdd($btn) {
    const projectId = $btn.data("project-id");
    const userId = parseInt($btn.siblings(".add-member-select").val());
    const role = $btn.siblings(".add-member-role-select").val() || "Colaborador";

    if (!userId) return;

    ApiClient.addMember(projectId, userId, role)
      .done(() => Projects.openProject(projectId))
      .fail((xhr) => alert(xhr.responseJSON?.error || "Erro ao adicionar membro."));
  },

  handleRemove($btn) {
    const projectId = $btn.data("project-id");
    const userId = $btn.data("user-id");

    if (!confirm("Tem certeza que deseja remover este membro do projeto?")) return;

    ApiClient.removeMember(projectId, userId)
      .done(() => Projects.openProject(projectId))
      .fail((xhr) => alert(xhr.responseJSON?.error || "Erro ao remover membro."));
  }
};