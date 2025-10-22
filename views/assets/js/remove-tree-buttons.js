const treeActions = document.querySelector(".tree-actions");
if (treeActions) {
    treeActions.querySelectorAll("a").forEach((link) => {
        link.remove();
    });
}
