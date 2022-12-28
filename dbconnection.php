<?php

declare(strict_types=1);
require __DIR__ . '/hotelFunctions.php';

// $name = '';
// $transferCode = '';
// $arrival = '';
// $departure = '';
// $room = '';

// Check if input-dates already exist in the database
function availability()
{
    $db = connect('/yrgopelago.db');

    // If all the fields in the form are set:
    if (isset($_POST['name'], $_POST['transfer-code'], $_POST['arrival'], $_POST['departure'], $_POST['room'])) {

        // Create variables of all input-values and sanitize them
        $name = htmlspecialchars($_POST['name'], ENT_QUOTES);
        $transferCode = trim(htmlspecialchars($_POST['transfer-code'], ENT_QUOTES));
        $arrival = trim(htmlspecialchars($_POST['arrival'], ENT_QUOTES));
        $departure = trim(htmlspecialchars($_POST['departure'], ENT_QUOTES));
        $room = filter_var($_POST['room'], FILTER_SANITIZE_NUMBER_INT);

        //Prepare database query to check if dates are present in the database:
        $statement = $db->prepare(
            'SELECT * FROM bookings
        WHERE
        room_id = :room_id
        AND
        (arrival_date <= :arrival_date
        or arrival_date < :departure_date)
        AND
        (departure_date > :arrival_date or
        departure_date > :departure_date)'
        );
        // Bind booking-parameters... 
        $statement->bindParam(':arrival_date', $arrival, PDO::PARAM_STR);
        $statement->bindParam(':departure_date', $departure, PDO::PARAM_STR);
        $statement->bindParam(':room_id', $room, PDO::PARAM_INT);
        //...and execute the database query.
        $statement->execute();

        // Fetch all bookings as an associative array.
        $bookings = $statement->fetchAll(PDO::FETCH_ASSOC);

        // Check if transfercode is valid.
        if (isValidUuid($transferCode)) {
            // If the dates are available, that is, not present in the database:
            if (empty($bookings)) {
                // redirect('/confirmation.php'); 
                // convert query-answers to json and array_push them into bookings-array? then print_r as receipt to customer?
                $booking = 'INSERT INTO bookings (
                name,
                transfer_code,
                arrival_date,
                departure_date,
                room_id
                ) VALUES (
                :name,
                :transfer_code,
                :arrival_date,
                :departure_date,
                :room_id )';

                $statement = $db->prepare($booking);

                $statement->bindParam(':name', $name, PDO::PARAM_STR);
                $statement->bindParam(':transfer_code', $transferCode, PDO::PARAM_STR);
                $statement->bindParam(':arrival_date', $arrival, PDO::PARAM_STR);
                $statement->bindParam(':departure_date', $departure, PDO::PARAM_STR);
                $statement->bindParam(':room_id', $room, PDO::PARAM_INT);

                $statement->execute();

                // the booking get visible in the calendar
                $bookings = [
                    "start" => $arrival,
                    "end" => $departure,
                    "name" => $name,
                    "mask" => true,
                    "classes" => ["booking", $arrival, $departure],
                ];


                //     $calendar->addEvents($bookings);
                echo "Thank you for your reservation!";

            } else {
                echo "Sorry, the dates you have chosen are not available. Please choose other dates for your stay.";
            }
        } else {
            echo "Sorry, your transfercode is not valid.";
        }
    } 
};
