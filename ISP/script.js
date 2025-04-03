console.log("JS Loaded");

const signUpButton=document.getElementById('signUpButton');
const signInButton=document.getElementById('signInButton');
const signInForm=document.getElementById('signIn');
const signUpForm=document.getElementById('signup');

signUpButton.addEventListener('click',function(){
	signInForm.style.display="none";
	signUpForm.style.display="block";
})
signInButton.addEventListener('click',function(){
	signInForm.style.display="block";
	signUpForm.style.display="none";
})
// Function to show error messages
function showError(input, message) {
    let errorElement = input.parentNode.querySelector(".error-message");
    errorElement.textContent = message; // Set error message
}

// Function to clear error messages
function clearError(input) {
    let errorElement = input.parentNode.querySelector(".error-message");
    errorElement.textContent = ""; // Clear text instead of removing element
}

// Validation rules
function validateField(input) {
    const nameRegex = /^[A-Za-z]+$/;
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\[\]{}|;:,.<>?])[A-Za-z\d!@#$%^&*()_+\-=\[\]{}|;:,.<>?]{8,}$/;
    if (input.name === "fName" || input.name === "lName") {
        if (!nameRegex.test(input.value.trim())) {
            showError(input, "Only alphabets are allowed in this field!");
            return false;
        } else {
            clearError(input);
        }
    } else if (input.name === "email") {
        if (!emailRegex.test(input.value.trim())) {
            showError(input, "Please enter a valid email.");
            return false;
        } else {
            clearError(input);
        }
    } else if (input.name === "password") {
        if (!passwordRegex.test(input.value)) {
            showError(input, "Password must have 8+ chars, an uppercase letter, a number, and a special char.");
            return false;
        } else {
            clearError(input);
        }
    }
    return true;
}

// Attach real-time validation on user input
document.querySelectorAll('input').forEach(input => {
    input.addEventListener('input', function () {
        validateField(input);
    });
});

// Prevent form submission if validation fails
document.querySelectorAll("form").forEach(form => {
    form.addEventListener("submit", function (event) {
        let isValid = true;
        form.querySelectorAll("input").forEach(input => {
            if (!validateField(input)) {
                isValid = false;
            }
        });

        if (!isValid) {
            event.preventDefault(); // Stop form submission if validation fails
        }
    });
});

document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector("form[action='register.php']");
    const accountTypeSelect = document.getElementById("accountType");

    form.addEventListener("submit", function (event) {
        if (accountTypeSelect.value === "admin") {
            form.action = "admin_dashboard.php";  // Redirect if admin
        }
    });
});
// Debugging Click Events
document.querySelector("#signin-submit").addEventListener("click", function () {
    console.log("Sign-in button clicked!");
});

document.querySelector("#signup-submit").addEventListener("click", function () {
    console.log("Sign-up button clicked!");
});
