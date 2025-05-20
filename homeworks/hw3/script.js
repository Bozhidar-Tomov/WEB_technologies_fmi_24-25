const form = document.getElementById("registration-form");
const successMessage = document.getElementById("success");

form.addEventListener("submit", async (e) => {
  e.preventDefault();
  clearErrors();
  successMessage.textContent = "";

  const username = document.getElementById("username").value.trim();
  const name = document.getElementById("name").value.trim();
  const familyName = document.getElementById("family-name").value.trim();
  const email = document.getElementById("email").value.trim();
  const password = document.getElementById("password").value;
  const street = document.getElementById("street").value.trim();
  const city = document.getElementById("city").value.trim();
  const postalCode = document.getElementById("postal-code").value.trim();

  let isValid = true;

  clearInvalid();

  if (username.length < 3 || username.length > 10) {
    showError("username-error", "Потребителското име трябва да е между 3 и 10 символа.");
    setInvalid("username");
    isValid = false;
  }

  if (!name || name.length > 50) {
    showError("name-error", "Името е задължително и трябва да е до 50 символа.");
    setInvalid("name");
    isValid = false;
  }

  if (!familyName || familyName.length > 50) {
    showError("family-error", "Фамилията е задължителна и трябва да е до 50 символа.");
    setInvalid("family-name");
    isValid = false;
  }

  if (!/^[\w.-]+@[\w.-]+\.\w{2,}$/.test(email)) {
    showError("email-error", "Невалиден имейл адрес.");
    setInvalid("email");
    isValid = false;
  }

  if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d]{6,10}$/.test(password)) {
    showError(
      "password-error",
      "Паролата трябва да е между 6 и 10 символа и да съдържа малки, главни букви и цифри."
    );
    setInvalid("password");
    isValid = false;
  }

  if (postalCode && !/^(\d{4}|\d{5}-\d{4})$/.test(postalCode)) {
    showError("postal-error", "Пощенският код трябва да е във формат 1111 или 11111-1111.");
    setInvalid("postal-code");
    isValid = false;
  }

  if (!isValid) return;

  try {
    const response = await fetch("https://jsonplaceholder.typicode.com/users");
    const users = await response.json();
    const userExists = users.some((user) => user.username.toLowerCase() === username.toLowerCase());
    const emailExists = users.some((user) => user.email.toLowerCase() === email.toLowerCase());

    if (userExists) {
      showError("username-error", "Потребител с това потребителско име вече съществува.");
      setInvalid("username");
      return;
    }
    if (emailExists) {
      showError("email-error", "Потребител с този имейл адрес вече съществува.");
      setInvalid("email");
      return;
    }

    const newUser = {
      username,
      name: `${name} ${familyName}`,
      email,
      address: {
        street,
        city,
        zipcode: postalCode,
      },
      password,
    };

    const postResponse = await fetch("https://jsonplaceholder.typicode.com/users", {
      method: "POST",
      body: JSON.stringify(newUser),
      headers: { "Content-Type": "application/json" },
    });

    if (postResponse.ok) {
      successMessage.textContent = "Успешна регистрация! Добре дошли!";
      form.reset();
      clearInvalid();
      successMessage.classList.add("show");
      successMessage.setAttribute("role", "alert");
    } else {
      successMessage.textContent = "Грешка при регистрацията.";
      successMessage.style.color = "red";
    }
  } catch (error) {
    console.error("Грешка при заявка:", error);
    successMessage.textContent = "Възникна грешка при регистрацията.";
    successMessage.style.color = "red";
  }
});

function showError(id, message) {
  const el = document.getElementById(id);
  el.textContent = message;
}

function setInvalid(inputId) {
  const input = document.getElementById(inputId);
  input.classList.add("invalid");
}

function clearInvalid() {
  const inputs = document.querySelectorAll("input");
  inputs.forEach((input) => input.classList.remove("invalid"));
  successMessage.classList.remove("show");
}

function clearErrors() {
  document.querySelectorAll(".error").forEach((el) => (el.textContent = ""));
}
