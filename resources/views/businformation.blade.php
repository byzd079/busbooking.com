<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Bus Information</title>
</head>

<body>
    <h2>Add Bus Information</h2>
    <form action="submit_bus_info.php" method="POST">
        @csrf
        <label for="departing_time">Departing Time:</label><br>
        <input type="text" id="departing_time" name="departing_time"><br><br>

        <label for="coach_no">Coach Number:</label><br>
        <input type="text" id="coach_no" name="coach_no"><br><br>

        <label for="starting_point">Starting Point:</label><br>
        <input type="text" id="starting_point" name="starting_point"><br><br>

        <label for="ending_point">Ending Point:</label><br>
        <input type="text" id="ending_point" name="ending_point"><br><br>

        <label for="fare">Fare:</label><br>
        <input type="text" id="fare" name="fare"><br><br>

        <label for="coach_type">Coach Type:</label><br>
        <input type="text" id="coach_type" name="coach_type"><br><br>

        <label for="seats_available">Seats Available:</label><br>
        <input type="text" id="seats_available" name="seats_available"><br><br>

        <label for="view">View:</label><br>
        <input type= "text" id="view" name="view">
        <input type="submit" value="Submit">
    </form>
</body>

</html>
