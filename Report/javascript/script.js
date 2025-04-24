function openNav() {
  const sidenav = document.getElementById("mySidenav");
  if (sidenav.classList.contains("open")) {
    sidenav.classList.remove("open");
    document.body.focus();
  } else {
    sidenav.classList.add("open");
    sidenav.focus();
  }
}

function closeNav() {
  const sidenav = document.getElementById("mySidenav");
  sidenav.classList.remove("open");
  document.body.focus();
}

document.addEventListener("DOMContentLoaded", function () {
  const links = document.querySelectorAll(".sidenav a");

  links.forEach((link) => {
    link.addEventListener("click", function () {
      links.forEach((l) => l.classList.remove("active"));
      this.classList.add("active");
    });
  });

  links.forEach((link) => {
    link.addEventListener("keydown", function (event) {
      if (event.key === "Enter") {
        this.click();
      }
    });
  });
});

document.addEventListener("click", function (event) {
  const sidenav = document.getElementById("mySidenav");
  if (!sidenav.contains(event.target) && !event.target.closest("#navbuttons")) {
    sidenav.classList.remove("open");
  }
});

function scrollToSection(sectionId) {
  const section = document.getElementById(sectionId);
  if (section) {
    section.scrollIntoView({
      behavior: "smooth",
    });
  }
}
