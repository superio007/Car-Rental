<?php
session_start();
$jsonResponse = $_SESSION['jsonResponse'];
var_dump($jsonResponse);
?>
<script>
    const data = {
        vendorID: "ZR",
        pickup: "MELC",
        dropOff: "MELC",
        pickUpDateTime: "2024-11-30T10:00:00",
        dropOffDateTime: "2024-12-01T10:00:00",
        carCategory: "IFAR",
    };


    console.log("getQuote Data:", data);

    fetch("getTry.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify(data),
        })
        .then((response) => response.text()) // Parse as text to debug issues
        .then((text) => {
            try {
                const json = JSON.parse(text); // Try parsing as JSON
                console.log("Quote Response:", json);

                // Check if the results array exists
                if (Array.isArray(json.results) && json.results.length > 0) {
                    // Iterate over the results
                    json.results.forEach((item) => {
                        if (item.Name && item.Code && item.RateTotalAmount && item.CurrencyCode) {
                            const {
                                Name,
                                Code,
                                RateTotalAmount,
                                CurrencyCode
                            } = item;

                            // Select the payment info div for the corresponding car category
                            const paymentInfoDiv = document.querySelector(
                                `#showQuote_Hertz_${Code} #Pay_div`
                            );

                            // Update the HTML if the div exists
                            if (paymentInfoDiv) {
                                paymentInfoDiv.innerHTML = `
                                <div>
                                    <p>Car Name: ${Name}</p>
                                    <p>Rental Rate: ${RateTotalAmount} ${CurrencyCode}</p>
                                </div>
                            `;
                            }
                        } else {
                            console.error("Missing data in one of the results:", item);
                        }
                    });
                } else {
                    console.error("No results found in the response", json);
                }
            } catch (error) {
                console.error("Failed to parse JSON response:", text);
            }
        })
        .catch((error) => console.error("Fetch error:", error));
</script>