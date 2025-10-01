<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar contraseña</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- IMPORTANTE -->

<style>
:root {
    --color-principal: #FF671D;
    --color-fondo: #FFFFFF;
    --color-texto: #333333;
    --color-borde: #CCCCCC;
    --color-input-bg: #F9F9F9;
}

*, *::before, *::after {
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    background-color: var(--color-fondo);
    margin: 0;
    padding: 0;
}

/* Barra superior */
.barra-superior {
    background-color: var(--color-principal);
    color: white;
    padding: 20px 100px;
    font-size: 24px;
    text-align: left;
}

.barra-superior span {
    font-weight: bold;
}

/* Contenido */
.contenido {
    max-width: 500px;
    width: 90%;
    margin: 40px auto;
    padding: 20px;
    text-align: center;
}

h2 {
    color: var(--color-principal);
    margin-bottom: 15px;
    font-size: 24px;
}

p {
    color: var(--color-texto);
    margin-bottom: 25px;
    font-size: 16px;
}

/* Formulario */
form {
    display: flex;
    flex-direction: column;
    gap: 15px;
    align-items: stretch;
}

label {
    text-align: left;
    font-size: 16px;
    color: var(--color-texto);
}

input[type="text"] {
    padding: 12px;
    border: 1px solid var(--color-borde);
    border-radius: 4px;
    font-size: 16px;
    background-color: var(--color-input-bg);
    width: 100%;
}

button {
    padding: 14px;
    background-color: var(--color-principal);
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 17px;
    cursor: pointer;
    width: 100%;
}

button:hover {
    background-color: #e65c17;
}

/* Enlace volver al login */
a {
    font-size: 15px;
}

/* MEDIA QUERIES */

/* Tablets */
@media (max-width: 768px) {
    .barra-superior {
        padding: 20px 40px;
        font-size: 22px;
        text-align: center;
    }

    h2 {
        font-size: 22px;
    }

    p, label {
        font-size: 15px;
    }

    button {
        font-size: 16px;
    }
}

/* Móviles */
@media (max-width: 480px) {
    .barra-superior {
        padding: 15px 20px;
        font-size: 20px;
        text-align: center;
    }

    .contenido {
        margin: 30px auto;
        padding: 15px;
    }

    h2 {
        font-size: 20px;
    }

    p, label {
        font-size: 14px;
    }

    input[type="text"] {
        font-size: 15px;
        padding: 10px;
    }

    button {
        font-size: 15px;
        padding: 12px;
    }

    a {
        font-size: 14px;
    }
}
</style>
</head>
<body>

    <!-- Barra superior -->
    <div class="barra-superior">
        <p><span>Inter</span>empleo - Recuperar contraseña</p>
    </div>

    <!-- Contenido principal -->
    <div class="contenido">
        <h2>¿Olvidaste tu contraseña?</h2>
        <p>Introduce tu dirección de correo electrónico o usuario para restablecer tu contraseña.</p>

        <form method="post" action="procesar_recuperacion.php">
            <label for="email">Correo electrónico o usuario:</label>
            <input type="text" id="email" name="email" required>

            <button type="submit">Enviar instrucciones</button>
        </form>

        <div style="margin-top: 20px;">
            <a href="login_responsive.php" style="color: var(--color-principal); text-decoration: none;">← Volver al login</a>
        </div>
    </div>

</body>
</html>