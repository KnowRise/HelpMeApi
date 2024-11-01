<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Reset Password</title>
</head>
<body>
    <h1>Reset Password</h1>

    <form method="POST" action="{{ route('reset-password', [$token, $email])}}">
        @csrf
        <label for="password">New Password:</label>
        <input type="password" id="password" name="password" required>

        <label for="password_confirmation">Confirm New Password:</label>
        <input type="password" id="password_confirmation" name="password_confirmation" required>

        <button type="submit">Reset Password</button>
    </form>
</body>
</body>
</html>
