<div id="loginModal" class="w3-modal">
                <div class="w3-modal-content w3-animate-top w3-card-4">
                    <header class="w3-container w3-lime w3-center w3-padding-32">
                        <span onclick="document.getElementById('loginModal').style.display='none'" class="w3-button w3-lime w3-xlarge w3-display-topright">×</span>
                        <h2 class="w3-wide">
													<i class="w3-margin-right"></i>Log In
												</h2>
                    </header>
                    <form class="w3-container">
                        <p>
                            <label>
                                <i class="fa fa-user"></i> Username or Email
                            </label>
                        </p>
                        <input class="w3-input w3-border" type="text" placeholder="">
                        <p>
                            <label>
                                <i class="fa fa-lock"></i> Password
                            </label>
                        </p>
                        <input class="w3-input w3-border" type="password" placeholder="">
                        <button class="w3-button w3-block w3-lime w3-padding-16 w3-section w3-right">Log In
                            <i class="fa fa-check"></i>
                        </button>
                        <button type="button" class="w3-button w3-red w3-section" onclick="document.getElementById('loginModal').style.display='none'">Close
                            <i class="fa fa-remove"></i>
                        </button>
                        <p class="w3-right">Need an
                            <a href="#" class="w3-text-blue" onclick="document.getElementById('registerModal').style.display='block'">account?</a>
                        </p>
                    </form>
                </div>
            </div>
<script>
            // When the user clicks anywhere outside of the modal, close it
            var loginModal = document.getElementById('loginModal');
            window.onclick = function(event) {
                if (event.target == loginModal) {
                    loginModal.style.display = "none";
                }
            }
</script>