<div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewModalLabel">Target Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="viewModalBody">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>
</div>

<script>
function loadViewModal(id) {
    $.get("{{ route('admin.target.get', '') }}/" + id, function (response) {
        $('#viewModalBody').html(response.viewContent);
        $('#viewModal').modal('show');
    }).fail(function () {
        Swal.fire('Error', 'Could not load details.', 'error');
    });
}
</script>
