document.addEventListener("DOMContentLoaded", () => {
  // Registration form steps
  const nextButtons = document.querySelectorAll(".next-step")
  const prevButtons = document.querySelectorAll(".prev-step")
  const stepIndicators = document.querySelectorAll(".step-indicator")
  const stepLabels = document.querySelectorAll(".step-label")

  nextButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const nextStep = this.getAttribute("data-next")

      // Validate current step
      const currentStep = Number.parseInt(nextStep) - 1
      if (!validateStep(currentStep)) {
        return
      }

      // Hide all step panes
      document.querySelectorAll(".step-pane").forEach((pane) => {
        pane.classList.remove("active")
      })

      // Show the next step pane
      document.getElementById("step" + nextStep).classList.add("active")

      // Update step indicators
      stepIndicators.forEach((indicator) => {
        indicator.classList.remove("active")
      })
      stepIndicators[nextStep - 1].classList.add("active")

      // Update step labels
      stepLabels.forEach((label) => {
        label.classList.remove("active")
      })
      stepLabels[nextStep - 1].classList.add("active")
    })
  })

  prevButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const prevStep = this.getAttribute("data-prev")

      // Hide all step panes
      document.querySelectorAll(".step-pane").forEach((pane) => {
        pane.classList.remove("active")
      })

      // Show the previous step pane
      document.getElementById("step" + prevStep).classList.add("active")

      // Update step indicators
      stepIndicators.forEach((indicator) => {
        indicator.classList.remove("active")
      })
      stepIndicators[prevStep - 1].classList.add("active")

      // Update step labels
      stepLabels.forEach((label) => {
        label.classList.remove("active")
      })
      stepLabels[prevStep - 1].classList.add("active")
    })
  })

  // Validate step
  function validateStep(step) {
    if (step === 1) {
      const name = document.getElementById("name").value
      const username = document.getElementById("username").value
      const email = document.getElementById("email").value
      const password = document.getElementById("password").value
      const confirmPassword = document.getElementById("confirm_password").value

      if (!name || !username || !email || !password || !confirmPassword) {
        alert("Please fill in all fields.")
        return false
      }

      if (!isValidEmail(email)) {
        alert("Please enter a valid email address.")
        return false
      }

      if (password.length < 8) {
        alert("Password must be at least 8 characters long.")
        return false
      }

      if (password !== confirmPassword) {
        alert("Passwords do not match.")
        return false
      }

      return true
    } else if (step === 2) {
      const phone = document.getElementById("phone").value
      const relativePhone = document.getElementById("relative_phone").value
      const aadharNumber = document.getElementById("aadhar_number").value

      if (!phone || !relativePhone || !aadharNumber) {
        alert("Please fill in all fields.")
        return false
      }

      if (!isValidPhone(phone) || !isValidPhone(relativePhone)) {
        alert("Please enter valid phone numbers with country code (e.g., +91...).")
        return false
      }

      return true
    }

    return true
  }

  // Validate email
  function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    return re.test(email)
  }

  // Validate phone
  function isValidPhone(phone) {
    const re = /^\+[0-9]{1,3}[0-9]{6,14}$/
    return re.test(phone)
  }

  // Map functionality
  const mapElement = document.getElementById("map")
  const latInput = document.getElementById("lat")
  const lngInput = document.getElementById("lng")
  const locationText = document.querySelector(".location-text")

  if (mapElement && latInput && lngInput) {
    // Initialize map
    let map // Declare map variable
    function initMap() {
      map = new google.maps.Map(mapElement, {
        center: { lat: 20.5937, lng: 78.9629 }, // Center of India
        zoom: 5,
        mapTypeControl: false,
      })

      // Add marker on click
      let marker = null

      map.addListener("click", (event) => {
        const location = event.latLng

        // Remove existing marker
        if (marker) {
          marker.setMap(null)
        }

        // Add new marker
        marker = new google.maps.Marker({
          position: location,
          map: map,
          draggable: true,
        })

        // Update inputs
        latInput.value = location.lat()
        lngInput.value = location.lng()
        locationText.textContent = `Selected: ${location.lat().toFixed(6)}, ${location.lng().toFixed(6)}`

        // Add drag event listener to marker
        marker.addListener("dragend", () => {
          const position = marker.getPosition()
          latInput.value = position.lat()
          lngInput.value = position.lng()
          locationText.textContent = `Selected: ${position.lat().toFixed(6)}, ${position.lng().toFixed(6)}`
        })
      })

      // Try to get user's current location
      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
          (position) => {
            const userLocation = {
              lat: position.coords.latitude,
              lng: position.coords.longitude,
            }

            map.setCenter(userLocation)
            map.setZoom(14)

            // Add marker at user's location
            marker = new google.maps.Marker({
              position: userLocation,
              map: map,
              draggable: true,
            })

            // Update inputs
            latInput.value = userLocation.lat
            lngInput.value = userLocation.lng
            locationText.textContent = `Selected: ${userLocation.lat.toFixed(6)}, ${userLocation.lng.toFixed(6)}`

            // Add drag event listener to marker
            marker.addListener("dragend", () => {
              const position = marker.getPosition()
              latInput.value = position.lat()
              lngInput.value = position.lng()
              locationText.textContent = `Selected: ${position.lat().toFixed(6)}, ${position.lng().toFixed(6)}`
            })
          },
          () => {
            console.log("Error: The Geolocation service failed.")
          },
        )
      }
    }
    initMap()
  }

  // Avatar preview
  const avatarInput = document.getElementById("avatar")
  const avatarPreview = document.getElementById("avatarPreview")

  if (avatarInput && avatarPreview) {
    avatarInput.addEventListener("change", function () {
      if (this.files && this.files[0]) {
        const reader = new FileReader()

        reader.onload = (e) => {
          avatarPreview.innerHTML = `<img src="${e.target.result}" alt="Avatar preview">`
        }

        reader.readAsDataURL(this.files[0])
      }
    })
  }
})

