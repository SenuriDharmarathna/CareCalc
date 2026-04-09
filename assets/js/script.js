document.addEventListener("DOMContentLoaded", () => {
  const roleSelect = document.getElementById("roleSelect");
  const formFields = document.getElementById("formFields");

  roleSelect.addEventListener("change", () => {
    const role = roleSelect.value;
    if (role === "customer") {
      formFields.innerHTML = `
        <input type="hidden" name="role" value="user">
        <div class="mb-3"><input class="form-control" name="username" placeholder="Username" required></div>
        <div class="mb-3"><input class="form-control" type="email" name="email" placeholder="Email" required></div>
        <div class="mb-3"><input class="form-control" type="password" name="password" placeholder="Password" required></div>
        <div class="mb-3"><input class="form-control" name="city" placeholder="City" required></div>
        <div class="mb-3"><input class="form-control" name="contact" placeholder="Contact" required></div>
      `;
    } else if (role === "admin") {
      formFields.innerHTML = `
        <input type="hidden" name="role" value="admin">
        <div class="mb-3"><input class="form-control" name="username" placeholder="Username" required></div>
        <div class="mb-3"><input class="form-control" type="email" name="email" placeholder="Email" required></div>
        <div class="mb-3"><input class="form-control" type="password" name="password" placeholder="Password" required></div>
      `;
    } else {
      formFields.innerHTML = "";
    }
  });
});
