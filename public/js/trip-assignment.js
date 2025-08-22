document.addEventListener("DOMContentLoaded", () => {
    const assignmentsTableBody = document.querySelector("#assignmentsTable tbody");
    const completedAssignmentsTableBody = document.querySelector("#completedAssignmentsTable tbody");

    // Fake demo data (Philippines)
    const currentAssignments = [
        {
            trip: "Trip #201",
            vehicle: "Toyota Hiace - Juan Dela Cruz",
            route: "Manila → Baguio",
            schedule: "Aug 16, 10:00 AM - ₱8,500",
            status: "Pending"
        },
        {
            trip: "Trip #202",
            vehicle: "Hyundai Starex - Maria Santos",
            route: "Cebu City → Dumaguete",
            schedule: "Aug 16, 1:30 PM - ₱6,200",
            status: "Active"
        }
    ];

    const completedAssignments = [
        {
            trip: "Trip #195",
            vehicle: "Mitsubishi L300 - Jose Rizal",
            route: "Davao City → General Santos",
            schedule: "Aug 15, 3:00 PM - ₱7,800",
            status: "Completed"
        },
        {
            trip: "Trip #196",
            vehicle: "Honda City - Ana Cruz",
            route: "Iloilo City → Bacolod",
            schedule: "Aug 15, 6:00 PM - ₱5,500",
            status: "Completed"
        }
    ];

    // Clear placeholder rows
    assignmentsTableBody.innerHTML = "";
    completedAssignmentsTableBody.innerHTML = "";

    // Populate Current Assignments
    currentAssignments.forEach(a => {
        const row = `
            <tr>
                <td>${a.trip}</td>
                <td>${a.vehicle}</td>
                <td>${a.route}</td>
                <td>${a.schedule}</td>
                <td>
                    <span class="badge bg-${a.status === "Active" ? "info" : "warning"}">
                        ${a.status}
                    </span>
                </td>
            </tr>
        `;
        assignmentsTableBody.insertAdjacentHTML("beforeend", row);
    });

    // Populate Completed Assignments
    completedAssignments.forEach(c => {
        const row = `
            <tr>
                <td>${c.trip}</td>
                <td>${c.vehicle}</td>
                <td>${c.route}</td>
                <td>${c.schedule}</td>
                <td><span class="badge bg-success">${c.status}</span></td>
            </tr>
        `;
        completedAssignmentsTableBody.insertAdjacentHTML("beforeend", row);
    });

    // Update Stats
    document.getElementById("pendingCount").innerText = currentAssignments.filter(a => a.status === "Pending").length;
    document.getElementById("activeCount").innerText = currentAssignments.filter(a => a.status === "Active").length;
    document.getElementById("completedCount").innerText = completedAssignments.length;

    // Update Badges
    document.querySelector(".card.main-card .badge.bg-primary").innerText = currentAssignments.length;
    document.querySelector(".card.main-card.mt-4 .badge.bg-success").innerText = completedAssignments.length;

    // Refresh button handler (reloads fake data)
    document.getElementById("refreshAssignments").addEventListener("click", () => {
        location.reload(); // simple refresh for now
    });
});