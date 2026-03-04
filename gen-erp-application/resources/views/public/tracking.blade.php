<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Your Shipment</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <h1 class="text-3xl font-bold text-center text-gray-800 mb-8">Track Your Shipment</h1>
            
            <!-- Tracking Form -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <form id="trackingForm" class="space-y-4">
                    <div>
                        <label for="trackingNumber" class="block text-sm font-medium text-gray-700 mb-2">
                            Enter Tracking Number
                        </label>
                        <input 
                            type="text" 
                            id="trackingNumber" 
                            name="trackingNumber"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="e.g., TRK123456789"
                            required
                        >
                    </div>
                    <button 
                        type="submit"
                        class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200"
                    >
                        Track Shipment
                    </button>
                </form>
            </div>

            <!-- Loading State -->
            <div id="loading" class="hidden text-center py-8">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <p class="mt-2 text-gray-600">Searching for your shipment...</p>
            </div>

            <!-- Error State -->
            <div id="error" class="hidden bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Tracking Not Found</h3>
                        <p class="mt-1 text-sm text-red-700" id="errorMessage">
                            We couldn't find a shipment with that tracking number. Please check the number and try again.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Results -->
            <div id="results" class="hidden">
                <!-- Shipment Info -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Shipment Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Tracking Number</p>
                            <p class="font-medium" id="shipmentTrackingNumber"></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Status</p>
                            <p class="font-medium" id="shipmentStatus"></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Recipient</p>
                            <p class="font-medium" id="shipmentRecipient"></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Destination</p>
                            <p class="font-medium" id="shipmentDestination"></p>
                        </div>
                        <div class="md:col-span-2">
                            <p class="text-sm text-gray-600">Estimated Delivery</p>
                            <p class="font-medium" id="estimatedDelivery"></p>
                        </div>
                    </div>
                </div>

                <!-- Tracking Timeline -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Tracking History</h2>
                    <div id="trackingTimeline" class="space-y-4">
                        <!-- Timeline items will be inserted here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('trackingForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const trackingNumber = document.getElementById('trackingNumber').value.trim();
            if (!trackingNumber) return;

            // Show loading state
            document.getElementById('loading').classList.remove('hidden');
            document.getElementById('error').classList.add('hidden');
            document.getElementById('results').classList.add('hidden');

            try {
                // Make API call to public tracking endpoint
                const response = await fetch(`/api/public/demo-tenant/track/${trackingNumber}`);
                const data = await response.json();

                document.getElementById('loading').classList.add('hidden');

                if (data.success) {
                    // Show results
                    displayTrackingResults(data.data);
                } else {
                    // Show error
                    showError(data.message || 'Tracking information not found');
                }
            } catch (error) {
                document.getElementById('loading').classList.add('hidden');
                showError('Unable to fetch tracking information. Please try again later.');
            }
        });

        function displayTrackingResults(data) {
            // Update shipment info
            document.getElementById('shipmentTrackingNumber').textContent = data.tracking_number;
            document.getElementById('shipmentStatus').textContent = data.status;
            document.getElementById('shipmentRecipient').textContent = data.recipient_name;
            document.getElementById('shipmentDestination').textContent = data.recipient_city;
            document.getElementById('estimatedDelivery').textContent = data.estimated_delivery || 'Not available';

            // Update tracking timeline
            const timeline = document.getElementById('trackingTimeline');
            timeline.innerHTML = '';

            data.tracking_events.forEach((event, index) => {
                const timelineItem = document.createElement('div');
                timelineItem.className = 'flex items-start space-x-3';
                
                timelineItem.innerHTML = `
                    <div class="flex-shrink-0">
                        <div class="w-3 h-3 rounded-full ${index === 0 ? 'bg-blue-600' : 'bg-gray-300'}"></div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-gray-900">${event.status}</p>
                            <p class="text-sm text-gray-500">${formatDate(event.event_time)}</p>
                        </div>
                        ${event.location ? `<p class="text-sm text-gray-600">${event.location}</p>` : ''}
                        ${event.description ? `<p class="text-sm text-gray-500">${event.description}</p>` : ''}
                    </div>
                `;
                
                timeline.appendChild(timelineItem);
            });

            document.getElementById('results').classList.remove('hidden');
        }

        function showError(message) {
            document.getElementById('errorMessage').textContent = message;
            document.getElementById('error').classList.remove('hidden');
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
        }
    </script>
</body>
</html>