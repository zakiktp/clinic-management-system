<?php
require_once 'core/init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = "Please enter email and password";
    } else {

        $stmt = $conn->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {

            // ✅ Check active user
            if (isset($user['status']) && $user['status'] != 1) {
                $error = "Account is inactive";
            } else {

                // ✅ SESSION
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name']    = $user['name'];
                $_SESSION['role']    = $user['role'];

                // ✅ FORCE PASSWORD CHANGE (IMPORTANT)
                if (!empty($user['force_password_change'])) {
                    header("Location: users/change_password.php");
                    exit;
                }

                // ✅ NORMAL LOGIN
                header("Location: index.php");
                exit;
            }

        } else {
            $error = "Invalid login credentials";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ansar Polyclinic Login</title>

<style>
:root{
    --primary:#0b6fa4;
    --primary-dark:#095a87;
    --glass:rgba(255,255,255,.14);
    --border:rgba(255,255,255,.22);
}

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family:Arial,sans-serif;
    min-height:100vh;
    overflow:hidden;
    color:#fff;
    background:#0d1b2a;
}

/* Background Layers */
.bg{
    position:fixed;
    inset:0;
    z-index:-3;
    background-size:cover;
    background-position:center;
    animation:bgRotate 32s infinite ease-in-out;
    will-change:background-image;
}

@keyframes bgRotate{
    0%,18%   {background-image:url('./visits/assets/images/theme1.jpg')}
    20%,38%  {background-image:url('./visits/assets/images/theme2.jpg')}
    40%,58%  {background-image:url('./visits/assets/images/theme3.jpg')}
    60%,78%  {background-image:url('./visits/assets/images/theme4.jpg')}
    80%,100% {background-image:url('./visits/assets/images/theme5.jpg')}
}

.overlay{
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.55);
    z-index:-2;
}



@keyframes particleMove{
    from{transform:translateY(0);}
    to{transform:translateY(-40px);}
}

/* Layout */
.wrapper{
    display:flex;
    min-height:100vh;
}

/* Branding */
.brand{
    flex:1;
    display:flex;
    flex-direction:column;
    justify-content:center;
    padding:5vw;
}

.logo{
    width:110px;
    margin-bottom:20px;
    animation:float 4s ease-in-out infinite;
}

@keyframes float{
    0%,100%{transform:translateY(0)}
    50%{transform:translateY(-8px)}
}

.brand h1{
    font-size:clamp(38px,4vw,62px);
    font-weight:900;
    line-height:1.05;
    margin-bottom:15px;
}

.brand p{
    font-size:18px;
    max-width:520px;
    opacity:.9;
    line-height:1.5;
}

.datetime{
    margin-top:35px;
}

#clock{
    font-size:26px;
    font-weight:700;
}

#date{
    font-size:15px;
    opacity:.85;
    margin-top:6px;
}

/* Login Panel */
.panel{
    width:min(430px,100%);
    display:flex;
    align-items:center;
    justify-content:center;
    padding:24px;
}

.login-box{
    width:100%;
    padding:32px;
    border-radius:22px;
    backdrop-filter:blur(14px);
    -webkit-backdrop-filter:blur(14px);
    background:var(--glass);
    border:1px solid var(--border);
    box-shadow:0 12px 30px rgba(0,0,0,.35);
}

.login-box h2{
    font-size:30px;
    font-weight:800;
    margin-bottom:8px;
}

.login-box small{
    display:block;
    margin-bottom:24px;
    opacity:.85;
}

.login-box input{
    width:100%;
    padding:14px;
    margin-bottom:14px;
    border:none;
    border-radius:10px;
    font-size:15px;
    outline:none;
}

.login-box button{
    width:100%;
    padding:14px;
    border:none;
    border-radius:10px;
    background:var(--primary);
    color:#fff;
    font-size:15px;
    font-weight:700;
    cursor:pointer;
    transition:.25s;
}

.login-box button:hover{
    background:var(--primary-dark);
}

.error{
    color:#ffd7d7;
    margin-top:14px;
    font-size:14px;
}

.footer{
    margin-top:18px;
    text-align:center;
    font-size:14px;
}

.footer a{
    color:#fff;
    text-decoration:none;
}

/* Tablet */
@media(max-width:992px){
    .brand{
        display:none;
    }

    .panel{
        width:100%;
    }

    .wrapper{
        justify-content:center;
    }
}

/* Mobile */
@media(max-width:576px){
    .login-box{
        padding:24px 18px;
        border-radius:16px;
    }

    .login-box h2{
        font-size:24px;
    }

    .login-box input,
    .login-box button{
        padding:12px;
        font-size:14px;
    }

    .particles{
        display:none;
    }
}
.password-wrap{
    position:relative;
    margin-bottom:14px;
}

.password-wrap input{
    width:100%;
    padding:14px 46px 14px 14px;
    margin-bottom:0;
}

.toggle-pass{
    position:absolute;
    right:14px;
    top:50%;
    transform:translateY(-50%);
    cursor:pointer;
    font-size:18px;
    user-select:none;
    color:#555;
}
</style>
</head>
<body>

<div class="bg"></div>
<div class="overlay"></div>
<div class="particles"></div>

<div class="wrapper">

    <!-- LEFT BRANDING -->
    <section class="brand">
        <img src="./visits/assets/images/logo_clinic.png" class="logo" alt="Clinic Logo">

        <h1>ANSAR<br>POLYCLINIC</h1>

        <p>
            Trusted Healthcare Management Platform for Modern Clinics, 
            Reception, Doctors & Administration.
        </p>

        <div class="datetime">
            <div id="clock">00:00:00</div>
            <div id="date">Loading Date...</div>
        </div>
    </section>

    <!-- LOGIN PANEL -->
    <section class="panel">
        <div class="login-box">

            <h2>Welcome Back</h2>
            <small>Login to access Clinic Dashboard</small>

            <form method="POST">
                <input type="email" name="email" placeholder="Email Address" required>
                <div class="password-wrap">
                    <input type="password" 
                        name="password" 
                        id="password"
                        placeholder="Password" 
                        required>

                    <span class="toggle-pass" onclick="togglePassword()">👁</span>
                </div>

                <button type="submit">Login</button>
            </form>

            <?php if(isset($error)): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="footer">
                <a href="forgot_password.php">Forgot Password?</a>
            </div>

        </div>
    </section>

</div>

<script>
(function(){
    const clock=document.getElementById('clock');
    const date=document.getElementById('date');

    function tick(){
        const now=new Date();

        clock.textContent=now.toLocaleTimeString([],{
            hour:'2-digit',
            minute:'2-digit',
            second:'2-digit'
        });

        date.textContent=now.toLocaleDateString([],{
            weekday:'long',
            day:'numeric',
            month:'long',
            year:'numeric'
        });
    }

    tick();
    setInterval(tick,1000);
})();
</script>
<script>
function togglePassword(){
    const pass = document.getElementById('password');
    const eye  = document.querySelector('.toggle-pass');

    if(pass.type === 'password'){
        pass.type = 'text';
        eye.textContent = '🙈';
    }else{
        pass.type = 'password';
        eye.textContent = '👁';
    }
}
</script>
</body>
</html>