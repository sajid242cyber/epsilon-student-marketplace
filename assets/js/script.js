/* =========================================================
   Epsilon - Global JavaScript
   ========================================================= */

document.addEventListener("DOMContentLoaded", function () {
  // Enable Bootstrap tooltips globally, if any are present
  var tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
  tooltipTriggerList.forEach(function (el) {
    new bootstrap.Tooltip(el);
  });

  // Auto-dismiss alert messages after 4 seconds
  document.querySelectorAll(".alert-auto-dismiss").forEach(function (alertEl) {
    setTimeout(function () {
      var bsAlert = bootstrap.Alert.getOrCreateInstance(alertEl);
      bsAlert.close();
    }, 4000);
  });

  // Show / hide password buttons (the eye icon beside a password box)
  document.querySelectorAll(".toggle-password").forEach(function (button) {
    button.addEventListener("click", function () {
      var field = document.getElementById(button.dataset.target);
      if (!field) return;

      var nowVisible = field.type === "password";
      field.type = nowVisible ? "text" : "password";
      button.title = nowVisible ? "Hide password" : "Show password";
      button.innerHTML = nowVisible
        ? '<i class="bi bi-eye-slash"></i>'
        : '<i class="bi bi-eye"></i>';
    });
  });

  // Password rules + confirm check on the registration form
  var pass = document.getElementById("password");
  var confirmPass = document.getElementById("confirm_password");
  var matchHint = document.getElementById("passwordMatchHint");

  if (pass && confirmPass) {
    var checkPasswords = function () {
      // Rule 1: at least 6 characters and at least one symbol
      var longEnough = pass.value.length >= 6;
      var hasSymbol = /[^A-Za-z0-9]/.test(pass.value);

      if (pass.value && !longEnough) {
        pass.setCustomValidity("Password must be at least 6 characters long");
      } else if (pass.value && !hasSymbol) {
        pass.setCustomValidity("Password must include at least one symbol, e.g. ! @ # $ %");
      } else {
        pass.setCustomValidity("");
      }

      // Rule 2: both boxes must match
      var matches = confirmPass.value === pass.value;
      confirmPass.setCustomValidity(
        confirmPass.value && !matches ? "Passwords do not match" : ""
      );

      if (matchHint) {
        if (!confirmPass.value) {
          matchHint.textContent = "";
          matchHint.className = "form-text";
        } else if (matches) {
          matchHint.textContent = "Passwords match";
          matchHint.className = "form-text text-success";
        } else {
          matchHint.textContent = "Passwords do not match";
          matchHint.className = "form-text text-danger";
        }
      }
    };

    pass.addEventListener("input", checkPasswords);
    confirmPass.addEventListener("input", checkPasswords);
  }

  // Phone number: keep it to 11 digits only
  var phone = document.getElementById("phone");
  if (phone) {
    phone.addEventListener("input", function () {
      // strip anything that is not a digit, then cap the length
      phone.value = phone.value.replace(/\D/g, "").slice(0, 11);
      phone.setCustomValidity(
        phone.value.length === 11 ? "" : "Phone number must be exactly 11 digits"
      );
    });
  }

  // Show the "new category" box only when the seller chooses to add one
  var categorySelect = document.getElementById("category_id");
  var newCategoryWrap = document.getElementById("newCategoryWrap");
  var newCategoryInput = document.getElementById("new_category");
  if (categorySelect && newCategoryWrap) {
    var toggleNewCategory = function () {
      var isAddingNew = categorySelect.value === "new";
      newCategoryWrap.classList.toggle("d-none", !isAddingNew);
      // Only require the text box while it is actually visible
      if (newCategoryInput) {
        newCategoryInput.required = isAddingNew;
        if (isAddingNew) {
          newCategoryInput.focus();
        }
      }
    };
    categorySelect.addEventListener("change", toggleNewCategory);
    toggleNewCategory();
  }

  /*
   * Image picker for the product post / edit forms.
   *
   * A file input normally REPLACES its whole list every time you choose
   * something, so picking a second photo used to throw the first one away.
   * We keep our own list, add each new pick to it, and write the combined
   * list back into the input so the form still submits every file.
   * Each thumbnail also gets an X button to take that photo back out.
   */
  var imageInput = document.getElementById("product_images");
  var previewWrap = document.getElementById("imagePreviewWrap");

  if (imageInput && previewWrap) {
    var MAX_IMAGES = 5;
    var MAX_SIZE = 3 * 1024 * 1024; // 3 MB, same limit as the server
    var chosenFiles = [];

    // Copy our list into the real input so it gets submitted with the form
    var syncInput = function () {
      var data = new DataTransfer();
      chosenFiles.forEach(function (file) {
        data.items.add(file);
      });
      imageInput.files = data.files;
    };

    var renderPreviews = function () {
      previewWrap.innerHTML = "";

      if (chosenFiles.length === 0) {
        return;
      }

      var counter = document.createElement("div");
      counter.className = "col-12 small text-muted mb-1";
      counter.textContent =
        chosenFiles.length + " of " + MAX_IMAGES + " photo" +
        (chosenFiles.length > 1 ? "s" : "") + " selected";
      previewWrap.appendChild(counter);

      chosenFiles.forEach(function (file, index) {
        var col = document.createElement("div");
        col.className = "col-4 col-md-3";

        var reader = new FileReader();
        reader.onload = function (e) {
          col.innerHTML =
            '<div class="position-relative">' +
              '<img src="' + e.target.result + '" class="img-fluid rounded border">' +
              '<button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1 py-0 px-2"' +
              ' title="Remove this photo">&times;</button>' +
            '</div>';

          col.querySelector("button").addEventListener("click", function () {
            chosenFiles.splice(index, 1);
            syncInput();
            renderPreviews();
          });
        };
        reader.readAsDataURL(file);

        previewWrap.appendChild(col);
      });
    };

    imageInput.addEventListener("change", function () {
      var rejected = [];

      Array.from(imageInput.files).forEach(function (file) {
        if (chosenFiles.length >= MAX_IMAGES) {
          rejected.push(file.name + " (limit is " + MAX_IMAGES + " photos)");
          return;
        }
        if (!file.type.startsWith("image/")) {
          rejected.push(file.name + " (not an image)");
          return;
        }
        if (file.size > MAX_SIZE) {
          rejected.push(file.name + " (larger than 3 MB)");
          return;
        }
        // Skip anything already picked
        var alreadyAdded = chosenFiles.some(function (f) {
          return f.name === file.name && f.size === file.size;
        });
        if (!alreadyAdded) {
          chosenFiles.push(file);
        }
      });

      syncInput();
      renderPreviews();

      if (rejected.length > 0) {
        alert("These files were not added:\n\n" + rejected.join("\n"));
      }
    });
  }
});
