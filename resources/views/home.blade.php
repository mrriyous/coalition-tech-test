@extends('layouts.app')

@section('title', 'Product Management System')

@section('content')
<div class="row">
    <div class="col-lg-12 mx-auto">
        <!-- Product Form -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-plus me-2"></i>
                    Add New Product
                </h5>
            </div>
            <div class="card-body">
                <form id="productForm">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="name" class="form-label fw-semibold">Product Name</label>
                            <input type="text" class="form-control" id="name" name="name" placeholder="Enter product name" required>
                        </div>
                        <div class="col-md-4">
                            <label for="quantity_in_stock" class="form-label fw-semibold">Quantity in Stock</label>
                            <input type="number" class="form-control" id="quantity_in_stock" name="quantity_in_stock" min="0" placeholder="0" required>
                        </div>
                        <div class="col-md-4">
                            <label for="price_per_item" class="form-label fw-semibold">Price per Item</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="price_per_item" name="price_per_item" min="0" step="0.01" placeholder="0.00" required>
                            </div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>
                                Add Product
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Products List -->
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    Products List
                </h5>
            </div>
            <div class="card-body">
                <div id="productsList">
                    <!-- Products will be loaded here via JavaScript -->
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('modals')
<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>
                    Edit Product
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editForm">
                    <input type="hidden" id="edit_id" name="id">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label fw-semibold">Product Name</label>
                        <input type="text" class="form-control" id="edit_name" name="name" placeholder="Enter product name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_quantity_in_stock" class="form-label fw-semibold">Quantity in Stock</label>
                        <input type="number" class="form-control" id="edit_quantity_in_stock" name="quantity_in_stock" min="0" placeholder="0" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_price_per_item" class="form-label fw-semibold">Price per Item</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" id="edit_price_per_item" name="price_per_item" min="0" step="0.01" placeholder="0.00" required>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>
                    Cancel
                </button>
                <button type="button" class="btn btn-primary" id="saveEditBtn">
                    <i class="fas fa-save me-2"></i>
                    Save Changes
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let editModal;
    let currentEditId = null;

    document.addEventListener('DOMContentLoaded', function() {
        // Initialize modal
        editModal = new bootstrap.Modal(document.getElementById('editModal'));
        
        // Load products on page load
        loadProducts();

        // Handle form submission
        const productForm = document.getElementById('productForm');
        productForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = {
                name: document.getElementById('name').value,
                quantity_in_stock: parseInt(document.getElementById('quantity_in_stock').value),
                price_per_item: parseFloat(document.getElementById('price_per_item').value)
            };

            // Show loading state
            const submitBtn = productForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Adding...';
            submitBtn.disabled = true;

            // Send form data via Axios
            window.axios.post('/web-api/products/create', formData)
                .then(response => {
                    // Show success message
                    showAlert('Product added successfully!', 'success', 'form');
                    
                    // Reset form
                    productForm.reset();
                    
                    // Reload products list
                    loadProducts();
                })
                .catch(error => {
                    let errorMessage = 'An error occurred. Please try again.';
                    if (error.response && error.response.data && error.response.data.errors) {
                        errorMessage = Object.values(error.response.data.errors).flat().join('<br>');
                    }
                    showAlert(errorMessage, 'danger', 'form');
                })
                .finally(() => {
                    // Reset button state
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
        });

        // Handle edit form submission
        document.getElementById('saveEditBtn').addEventListener('click', function() {
            const formData = {
                name: document.getElementById('edit_name').value,
                quantity_in_stock: parseInt(document.getElementById('edit_quantity_in_stock').value),
                price_per_item: parseFloat(document.getElementById('edit_price_per_item').value)
            };

            // Show loading state
            const saveBtn = document.getElementById('saveEditBtn');
            const originalText = saveBtn.innerHTML;
            saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
            saveBtn.disabled = true;

            // Send update request via Axios
            window.axios.put(`/web-api/products/${currentEditId}`, formData)
                .then(response => {
                    // Show success message
                    showAlert('Product updated successfully!', 'success', 'modal');
                    
                    // Close modal
                    editModal.hide();
                    
                    // Reload products list
                    loadProducts();
                })
                .catch(error => {
                    let errorMessage = 'An error occurred. Please try again.';
                    if (error.response && error.response.data && error.response.data.errors) {
                        errorMessage = Object.values(error.response.data.errors).flat().join('<br>');
                    }
                    showAlert(errorMessage, 'danger', 'modal');
                })
                .finally(() => {
                    // Reset button state
                    saveBtn.innerHTML = originalText;
                    saveBtn.disabled = false;
                });
        });
    });

    function loadProducts() {
        window.axios.get('/web-api/products')
            .then(response => {
                displayProducts(response.data);
            })
            .catch(error => {
                console.error('Error loading products:', error);
                showAlert('Error loading products', 'danger', 'container');
            });
    }

    function displayProducts(products) {
        const productsList = document.getElementById('productsList');
        
        if (products.length === 0) {
            productsList.innerHTML = '<div class="text-center py-5"><i class="fas fa-box-open fa-3x text-muted mb-3"></i><p class="text-muted">No products found.</p></div>';
            return;
        }

        let totalSum = 0;
        let html = '<div class="table-responsive"><table class="table table-hover">';
        html += '<thead><tr>';
        html += '<th>Product Name</th>';
        html += '<th>Quantity in Stock</th>';
        html += '<th>Price per Item</th>';
        html += '<th>Datetime Submitted</th>';
        html += '<th>Total Value</th>';
        html += '<th>Actions</th>';
        html += '</tr></thead><tbody>';

        products.forEach(product => {
            const totalValue = product.total_value;
            totalSum += totalValue;
            const createdDate = new Date(product.datetime_submitted).toLocaleString();
            
            html += '<tr>';
            html += `<td><strong class="text-dark">${product.name}</strong></td>`;
            html += `<td><span class="badge bg-info">${product.quantity_in_stock_formatted}</span></td>`;
            html += `<td><span class="fw-semibold text-success">$${product.price_per_item_formatted}</span></td>`;
            html += `<td><small class="text-muted">${createdDate}</small></td>`;
            html += `<td><span class="badge bg-success">$${product.total_value_formatted}</span></td>`;
            html += `<td>
                <button class="btn btn-sm btn-outline-primary me-1" onclick="editProduct('${product.id}', '${product.name}', ${product.quantity_in_stock}, ${product.price_per_item})" title="Edit">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteProduct('${product.id}')" title="Delete">
                    <i class="fas fa-trash"></i>
                </button>
            </td>`;
            html += '</tr>';
        });

        // Add total row
        html += `<tr class="table-warning fw-bold">
            <td colspan="4" class="text-end">Total Sum:</td>
            <td><span class="badge bg-warning">$${totalSum.toFixed(2)}</span></td>
            <td></td>
        </tr>`;

        html += '</tbody></table></div>';
        productsList.innerHTML = html;
    }

    function editProduct(id, name, quantity, price) {
        currentEditId = id;
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_quantity_in_stock').value = quantity;
        document.getElementById('edit_price_per_item').value = price;
        editModal.show();
    }

    function deleteProduct(id) {
        if (confirm('Are you sure you want to delete this product?')) {
            window.axios.delete(`/web-api/products/${id}`)
                .then(response => {
                    showAlert('Product deleted successfully!', 'success', 'container');
                    loadProducts();
                })
                .catch(error => {
                    showAlert('Error deleting product', 'danger', 'container');
                });
        }
    }

    function showAlert(message, type, location = 'container') {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show`;
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Insert alert based on location
        if (location === 'form') {
            // Insert after the form card
            const formCard = document.querySelector('.card.shadow-sm.mb-4');
            formCard.parentNode.insertBefore(alert, formCard.nextSibling);
        } else if (location === 'modal') {
            // Insert in the modal body
            const modalBody = document.querySelector('.modal-body');
            modalBody.insertBefore(alert, modalBody.firstChild);
        } else {
            // Default: insert at the top of the container
            const container = document.querySelector('.container');
            container.insertBefore(alert, container.firstChild);
        }
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            if (alert.parentNode) {
                alert.remove();
            }
        }, 5000);
    }
</script>
@endsection 