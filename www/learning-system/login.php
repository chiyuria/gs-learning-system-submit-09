<?php
// login.php
?>
<!doctype html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Learning System | Login</title>

    <link rel="stylesheet" href="./assets/css/tokens.css">
    <link rel="stylesheet" href="./assets/css/reset.css">
    <link rel="stylesheet" href="./assets/css/typography.css">
    <link rel="stylesheet" href="./assets/css/layout.css">

    <link rel="stylesheet" href="./assets/css/components/buttons.css">
    <link rel="stylesheet" href="./assets/css/components/forms.css">
    <link rel="stylesheet" href="./assets/css/components/badges.css">

    <link rel="stylesheet" href="./assets/css/scroll.css">
    <link rel="stylesheet" href="./assets/css/pages/login.css">
</head>

<body class="page-login">
    <header class="app-header">
        <h1 class="header-title">Learning System</h1>
    </header>

    <main class="main-wrapper">
        <div class="col">
            <div class="col-header">
                <h2>Login</h2>
            </div>

            <div class="col-content">
                <form action="login_action.php" method="post">
                    <fieldset>
                        <div class="form-row">
                            <label class="form-label" for="login_code">User ID</label>
                            <input
                                type="text"
                                name="login_code"
                                id="login_code"
                                class="input-base input-text"
                                required
                                autocomplete="username"
                                placeholder="Enter your ID">
                        </div>

                        <div class="form-row">
                            <label class="form-label" for="password_hash">Password</label>
                            <input
                                type="password"
                                name="password_hash"
                                id="password_hash"
                                class="input-base input-text"
                                required
                                autocomplete="current-password"
                                placeholder="Enter your password">
                        </div>

                        <div class="btn-row">
                            <button type="submit" class="btn btn-primary">Login</button>
                        </div>
                    </fieldset>
                </form>
            </div>
        </div>
    </main>
</body>

</html>