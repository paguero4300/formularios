/**
 * JavaScript personalizado
 * Panel administrativo Dr Security
 */

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips de Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Inicializar popovers de Bootstrap
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Manejar la visibilidad del sidebar en dispositivos móviles
    var sidebarToggle = document.getElementById('sidebarToggle');
    var sidebar = document.querySelector('.sidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });
    }
    
    // Manejar confirmaciones de eliminación
    var deleteButtons = document.querySelectorAll('.btn-delete');
    
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            if (!confirm('¿Estás seguro de que deseas eliminar este elemento? Esta acción no se puede deshacer.')) {
                e.preventDefault();
            }
        });
    });
    
    // Manejar cambios de estado
    var statusToggles = document.querySelectorAll('.status-toggle');
    
    statusToggles.forEach(function(toggle) {
        toggle.addEventListener('change', function() {
            var form = this.closest('form');
            if (form) {
                form.submit();
            }
        });
    });
    
    // Manejar formularios dinámicos
    var addFieldButton = document.getElementById('addFieldButton');
    var fieldContainer = document.getElementById('fieldContainer');
    var fieldTemplate = document.getElementById('fieldTemplate');
    
    if (addFieldButton && fieldContainer && fieldTemplate) {
        addFieldButton.addEventListener('click', function() {
            var newField = fieldTemplate.content.cloneNode(true);
            var fieldCount = fieldContainer.querySelectorAll('.form-field-card').length;
            
            // Actualizar IDs y nombres
            var inputs = newField.querySelectorAll('input, select');
            inputs.forEach(function(input) {
                var name = input.getAttribute('name');
                if (name) {
                    input.setAttribute('name', name.replace('__INDEX__', fieldCount));
                }
                
                var id = input.getAttribute('id');
                if (id) {
                    input.setAttribute('id', id.replace('__INDEX__', fieldCount));
                }
            });
            
            // Actualizar labels
            var labels = newField.querySelectorAll('label');
            labels.forEach(function(label) {
                var forAttr = label.getAttribute('for');
                if (forAttr) {
                    label.setAttribute('for', forAttr.replace('__INDEX__', fieldCount));
                }
            });
            
            // Añadir el nuevo campo al contenedor
            fieldContainer.appendChild(newField);
            
            // Inicializar el botón de eliminación
            var removeButton = fieldContainer.querySelector('.form-field-card:last-child .btn-remove-field');
            if (removeButton) {
                removeButton.addEventListener('click', function() {
                    this.closest('.form-field-card').remove();
                });
            }
        });
    }
    
    // Inicializar botones de eliminación de campos existentes
    var removeFieldButtons = document.querySelectorAll('.btn-remove-field');
    
    removeFieldButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            this.closest('.form-field-card').remove();
        });
    });
    
    // Manejar vista previa de formularios
    var previewButton = document.getElementById('previewFormButton');
    var previewModal = document.getElementById('previewFormModal');
    
    if (previewButton && previewModal) {
        previewButton.addEventListener('click', function() {
            var formTitle = document.getElementById('titulo').value || 'Formulario sin título';
            var formDescription = document.getElementById('descripcion').value || 'Sin descripción';
            
            // Actualizar título y descripción en el modal
            var modalTitle = previewModal.querySelector('.modal-title');
            var modalDescription = previewModal.querySelector('.modal-description');
            
            if (modalTitle) modalTitle.textContent = formTitle;
            if (modalDescription) modalDescription.textContent = formDescription;
            
            // Generar campos del formulario en el modal
            var modalBody = previewModal.querySelector('.modal-form-fields');
            if (modalBody) {
                modalBody.innerHTML = '';
                
                var fieldCards = document.querySelectorAll('.form-field-card');
                fieldCards.forEach(function(card) {
                    var fieldType = card.querySelector('select[name*="tipo_campo"]').value;
                    var fieldLabel = card.querySelector('input[name*="etiqueta"]').value;
                    var fieldRequired = card.querySelector('input[name*="requerido"]').checked;
                    
                    var fieldHtml = '';
                    var requiredAttr = fieldRequired ? 'required' : '';
                    
                    switch (fieldType) {
                        case 'lugar':
                            fieldHtml = `
                                <div class="mb-3">
                                    <label class="form-label">${fieldLabel}</label>
                                    <input type="text" class="form-control" ${requiredAttr}>
                                </div>
                            `;
                            break;
                        case 'fecha_hora':
                            fieldHtml = `
                                <div class="mb-3">
                                    <label class="form-label">${fieldLabel}</label>
                                    <input type="datetime-local" class="form-control" ${requiredAttr}>
                                </div>
                            `;
                            break;
                        case 'ubicacion_gps':
                            fieldHtml = `
                                <div class="mb-3">
                                    <label class="form-label">${fieldLabel}</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" placeholder="Latitud" ${requiredAttr}>
                                        <input type="text" class="form-control" placeholder="Longitud" ${requiredAttr}>
                                    </div>
                                </div>
                            `;
                            break;
                        case 'comentario':
                            fieldHtml = `
                                <div class="mb-3">
                                    <label class="form-label">${fieldLabel}</label>
                                    <textarea class="form-control" rows="3" ${requiredAttr}></textarea>
                                </div>
                            `;
                            break;
                    }
                    
                    modalBody.innerHTML += fieldHtml;
                });
            }
            
            // Mostrar el modal
            var modal = new bootstrap.Modal(previewModal);
            modal.show();
        });
    }
    
    // Manejar ordenamiento de campos (si se implementa)
    var sortableContainer = document.getElementById('sortableFields');
    
    if (sortableContainer && typeof Sortable !== 'undefined') {
        new Sortable(sortableContainer, {
            handle: '.form-field-handle',
            animation: 150,
            onEnd: function(evt) {
                // Actualizar el orden de los campos
                var fields = sortableContainer.querySelectorAll('.form-field-card');
                fields.forEach(function(field, index) {
                    var orderInput = field.querySelector('input[name*="orden"]');
                    if (orderInput) {
                        orderInput.value = index + 1;
                    }
                });
            }
        });
    }
});
