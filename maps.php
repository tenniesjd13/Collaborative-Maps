<html>
<head>
        <title>Locations List</title>
        <meta name="viewport" content="initial-scale=1.0, user-scalable=no">
        <meta charset="utf-8">
        <link rel="stylesheet" href="styling.css"/>
</head>
<body>
        <h1 style="text-align:center;">Collaborative Google Maps</h1>
        <p style="text-align:center; text-decoration:none">
                <a class="link" href="tutorial.html">Tutorial</a>&nbsp;&nbsp;&nbsp;&nbsp;
                <a class="link" href="maps.php">Map</a>
        </p>
        <p id="list" style="display:none">
        <?php
        //PHP code is adapted from John Phillips' work
        $DB_USER = 'tenniesjd13';
        $DB_PASSWORD = 'tenniesjd13';

        //connect to the database
        try {
                $dbh = new PDO('mysql:host=localhost;dbname=tenniesjd13;charset=utf8', $DB_USER, $DB_PASSWORD);
                $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
                echo "Error!: " . $e->getMessage() . "<br>";
                die();
        }
        
        //executed when the form is submitted
        if(isset($_POST['submit'])) {
                $name = "";
                $description = "";
                $latitude = "";
                $longitude = "";
                $zoom = "";

                //checks each field to make sure they are not null and have a length > 0
                if(isset($_POST['name'], $_POST['description'], $_POST['latitude'],
                        $_POST['longitude'], $_POST['zoom']) && strlen($_POST['name']) > 0
                        && strlen($_POST['description']) > 0 && strlen($_POST['latitude']) > 0 
                        && strlen($_POST['longitude']) > 0 && strlen($_POST['zoom']) > 0) 
                {
                        //sanitizes the given variable
                        $name = $_POST['name'];
                        $name = trim($name);
                        $name = stripslashes($name);
                        $name = htmlspecialchars($name);
                        $name = preg_replace('/\n/', '', $name);

                        $description = $_POST['description'];
                        $description = trim($description);
                        $description = stripslashes($description);
                        $description = htmlspecialchars($description);
                        $description = preg_replace('/\n/', '', $description);

                        $latitude = $_POST['latitude'];
                        $latitude = trim($latitude);
                        $latitude = stripslashes($latitude);
                        $latitude = htmlspecialchars($latitude);
                        $latitude = preg_replace('/\n/', '', $latitude);

                        $longitude = $_POST['longitude'];
                        $longitude = trim($longitude);
                        $longitude = stripslashes($longitude);
                        $longitude = htmlspecialchars($longitude);
                        $longitude = preg_replace('/\n/', '', $longitude);

                        $zoom = $_POST['zoom'];
                        $zoom = trim($zoom);
                        $zoom = stripslashes($zoom);
                        $zoom = htmlspecialchars($zoom);
                        $zoom = preg_replace('/\n/', '', $zoom);

                        try {
                                $q = "insert into locations (id, name, description, latitude, longitude, zoomLevel) 
                                        values (null, ?, ?, ?, ?, ?)";
                                $stmt = $dbh->prepare($q);
                                $stmt->bindParam(1, $name); //bindParam prepares each parameter 
                                $stmt->bindParam(2, $description);
                                $stmt->bindParam(3, $latitude);
                                $stmt->bindParam(4, $longitude);
                                $stmt->bindParam(5, $zoom);
                                $stmt->execute();
                        } catch (PDOException $e) {
                                echo "<p>Error!: " . $e->getMessage() . "</p>";
                        }
                        
                } else { //sends an alert that there is an error in the field
                        $error = "There is an error in the form. Please correct it and try again.";
                        echo "<script>alert('$error');</script>";
                }
        }
        //here is our SQL query statement
        $q = "select * from locations order by id desc";

        $i = 0;
        //execute query and display the results
        foreach($dbh->query($q) as $row) {
                echo "$row[id], $row[description], $row[name], $row[latitude], $row[longitude], $row[zoomLevel]\n";
        }

         
        ?>
        </p>
        <div id="leftContainer">
        <div id="showlist"><h3 id="listHead">Locations<h3></div>
        <h3 style="text-align:center">Add Location</h3>
        <form id="add" method="post" name="addLocation">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name"/>
                <label for="description"><br><br>Description:</label>
                <input type="text" id="description" name="description"/>
                <label for="latitude"><br><br>Lat:</label>
                <input type="text" id="latitude" name="latitude" readonly/>
                <label for="longitude"><br><br>Lng:</label>
                <input type="text" id="longitude" name="longitude" readonly/>
                <label for="zoom"><br><br>Zoom:</label>
                <input type="text"  id="zoom" name="zoom" readonly/>
                <div style="display:inline;margin-top:15px;">
                  <button id="submit" type="submit" name="submit" value="Submit Location">Add Location</button>
                  <button id="reset" type ="reset">Clear</button>
                </div>
        </form>
        </div>
        <input id="pac-input" type="text" class="controls" placeholder="Search Box"/>
        <div id="map"></div>                
        

        <script>
                //initializes the map and sets defaults
                var map;

                //used to hold the most recent marker
                var markers = [];

                function initAutocomplete() {
                        var mapProp = {
                                center: new google.maps.LatLng(53.2734, -7.7783),
                                zoom: 8,
                                panControl: true,
                                zoomControl: true,
                                mapTypeControl: true,
                                scaleControl: true,
                                streetViewControl: true,
                                overviewMapControl: true,
                                rotateControl: true,
                                mapTypeId: "satellite",
                        };
                        map = new google.maps.Map(document.getElementById("map"), mapProp);

                        //search box code is adapted from the google maps api method of creating a search box
                        //which is licensed under the Apache 2.0 license
                        //grabs the search box
                        var input = document.getElementById("pac-input");
                        var searchBox = new google.maps.places.SearchBox(input);
                        map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);

                        map.addListener('bounds_changed', function() {
                                searchBox.setBounds(map.getBounds());
                        });

                        //creates a click listener that places a marker on the clicked position
                        map.addListener('click', function (e) {
                                placeMarkerFillForm(map, e.latLng);
                                map.setCenter(e.latLng);
                        });

                        searchBox.addListener('places_changed', function() {
                                var places = searchBox.getPlaces();

                                if (places.length == 0) {
                                        return;
                                }

                                // Clear out the old markers.
                                markers.forEach(function(marker) {
                                        marker.setMap(null);
                                });
                                markers = [];

                                // For each place, get the icon, name and location.
                                var bounds = new google.maps.LatLngBounds();
                                places.forEach(function(place) {
                                        if (!place.geometry) {
                                                console.log("Returned place contains no geometry");
                                                return;
                                        }
                                        var icon = {
                                                url: place.icon,
                                                size: new google.maps.Size(71, 71),
                                                origin: new google.maps.Point(0, 0),
                                                anchor: new google.maps.Point(17, 34),
                                                scaledSize: new google.maps.Size(25, 25)
                                        };

                                        // Create a marker for each place.
                                        markers.push(new google.maps.Marker({
                                                map: map,
                                                icon: icon,
                                                title: place.name,
                                                position: place.geometry.location
                                        }));

                                        if (place.geometry.viewport) {
                                                // Only geocodes have viewport.
                                                bounds.union(place.geometry.viewport);
                                        } else {
                                                bounds.extend(place.geometry.location);
                                        }
                                });
                                map.fitBounds(bounds);
                        });
                }

                //grabs the locations and adds an event listener to view that location on maps
                var entries = document.getElementById("showlist");
                entries.addEventListener('click', viewLocation, false);

                //displays the locations inside a div
                var list = document.getElementById("list").innerHTML;
                var selections = list.split("\n");
                for(var i = 0; i < selections.length; i++) {
                        var p = document.createElement("p");
                        p.innerHTML = selections[i];
                        entries.appendChild(p);
                }



                //contains code adapted from "Handling Events For Many Elements" by Kirupa
                function viewLocation(e) {
                        var clickedLocation;
                        if (e.target !== e.currentTarget) {
                                clickedLocation = e.target.innerHTML;
                        }
                        e.stopPropagation();
                        var data = clickedLocation.split(", ");
                        map.setCenter(new google.maps.LatLng(data[3], data[4]));
                        map.setZoom(parseInt(data[5]));
                }


                //deletes old marker if applicable, places new one, and auto populates the latitude, longitude, and zoom
                //fields for the user, helps prevent injections
                function placeMarkerFillForm(map, location) {
                        if(markers != 0) markers[0].setMap(null);
                        var marker = new google.maps.Marker({
                                position: location,
                                map: map,
                                animation: google.maps.Animation.DROP,
                        });
                        markers = [];
                        markers.push(marker);
                        markers[0].setMap(map);   

                        var lat = location.lat().toFixed(6);
                        var lng = location.lng().toFixed(6);
                        var zoom = map.getZoom();

                        //fills the latitude, longitude, and zoom of the form, elements that are not editable any other way
                        document.getElementById("latitude").value = lat;
                        document.getElementById("longitude").value = lng;
                        document.getElementById("zoom").value = zoom;
                }

                </script>
        <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDzEXkWBv5UwBou6YyYiiKlMCsz4sjBuiM&libraries=places&callback=initAutocomplete"></script>
</body>
</html>
