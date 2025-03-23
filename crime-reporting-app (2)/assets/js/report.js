document.addEventListener("DOMContentLoaded", () => {
  // Map functionality
  const mapElement = document.getElementById("map")
  const latInput = document.getElementById("lat")
  const lngInput = document.getElementById("lng")
  const locationText = document.querySelector(".location-text")
  const addressInput = document.getElementById("address")

  if (mapElement && latInput && lngInput) {
    // Initialize map
    const map = new google.maps.Map(mapElement, {
      center: { lat: 20.5937, lng: 78.9629 }, // Center of India
      zoom: 5,
      mapTypeControl: false,
    })

    // Initialize geocoder
    const geocoder = new google.maps.Geocoder()

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

      // Get address from coordinates
      geocoder.geocode({ location: location }, (results, status) => {
        if (status === "OK" && results[0]) {
          addressInput.value = results[0].formatted_address
        }
      })

      // Add drag event listener to marker
      marker.addListener("dragend", () => {
        const position = marker.getPosition()
        latInput.value = position.lat()
        lngInput.value = position.lng()
        locationText.textContent = `Selected: ${position.lat().toFixed(6)}, ${position.lng().toFixed(6)}`

        // Get address from coordinates
        geocoder.geocode({ location: position }, (results, status) => {
          if (status === "OK" && results[0]) {
            addressInput.value = results[0].formatted_address
          }
        })
      })
    })

    // Address search
    if (addressInput) {
      addressInput.addEventListener("blur", function () {
        const address = this.value

        if (address) {
          geocoder.geocode({ address: address }, (results, status) => {
            if (status === "OK" && results[0]) {
              const location = results[0].geometry.location

              // Update map
              map.setCenter(location)
              map.setZoom(14)

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

                // Get address from coordinates
                geocoder.geocode({ location: position }, (results, status) => {
                  if (status === "OK" && results[0]) {
                    addressInput.value = results[0].formatted_address
                  }
                })
              })
            }
          })
        }
      })
    }

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

          // Get address from coordinates
          geocoder.geocode({ location: userLocation }, (results, status) => {
            if (status === "OK" && results[0]) {
              addressInput.value = results[0].formatted_address
            }
          })

          // Add drag event listener to marker
          marker.addListener("dragend", () => {
            const position = marker.getPosition()
            latInput.value = position.lat()
            lngInput.value = position.lng()
            locationText.textContent = `Selected: ${position.lat().toFixed(6)}, ${position.lng().toFixed(6)}`

            // Get address from coordinates
            geocoder.geocode({ location: position }, (results, status) => {
              if (status === "OK" && results[0]) {
                addressInput.value = results[0].formatted_address
              }
            })
          })
        },
        () => {
          console.log("Error: The Geolocation service failed.")
        },
      )
    }
  }

  // Evidence preview
  const evidenceInput = document.getElementById("evidence")
  const evidencePreview = document.getElementById("evidencePreview")

  if (evidenceInput && evidencePreview) {
    evidenceInput.addEventListener("change", function () {
      if (this.files && this.files.length > 0) {
        for (let i = 0; i < this.files.length; i++) {
          const file = this.files[i]
          const reader = new FileReader()

          reader.onload = (e) => {
            const previewElement = document.createElement("div")
            previewElement.className = "evidence-preview"

            if (file.type.startsWith("image/")) {
              previewElement.innerHTML = `
                <img src="${e.target.result}" alt="Evidence preview">
                <div class="remove-btn" data-index="${i}">×</div>
              `
            } else if (file.type.startsWith("video/")) {
              previewElement.innerHTML = `
                <div style="display: flex; align-items: center; justify-content: center; height: 100%; background-color: #f1f5f9;">
                  <i class="fas fa-video" style="font-size: 2rem; color: #64748b;"></i>
                </div>
                <div class="remove-btn" data-index="${i}">×</div>
              `
            } else {
              previewElement.innerHTML = `
                <div style="display: flex; align-items: center; justify-content: center; height: 100%; background-color: #f1f5f9;">
                  <i class="fas fa-file" style="font-size: 2rem; color: #64748b;"></i>
                </div>
                <div class="remove-btn" data-index="${i}">×</div>
              `
            }

            evidencePreview.appendChild(previewElement)

            // Add remove button event listener
            const removeBtn = previewElement.querySelector(".remove-btn")
            if (removeBtn) {
              removeBtn.addEventListener("click", () => {
                previewElement.remove()
              })
            }
          }

          reader.readAsDataURL(file)
        }
      }
    })
  }
})

