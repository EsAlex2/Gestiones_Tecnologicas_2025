// assets/js/app.js

// Toggle de tema
function initThemeToggle() {
  const toggleBtn = document.querySelector('[data-theme-toggle]');
  const html = document.documentElement;
  
  // Verificar preferencia guardada
  const savedTheme = localStorage.getItem('theme') || 'dark';
  html.classList.toggle('light', savedTheme === 'light');
  
  if (toggleBtn) {
    toggleBtn.textContent = savedTheme === 'light' ? '🌙' : '☀️';
    toggleBtn.addEventListener('click', () => {
      const isLight = html.classList.toggle('light');
      localStorage.setItem('theme', isLight ? 'light' : 'dark');
      toggleBtn.textContent = isLight ? '🌙' : '☀️';
    });
  }
}

// Sistema de notificaciones Toast
function showToast(message, type = 'info', duration = 5000) {
  const toast = document.getElementById('toast');
  if (!toast) return;
  
  // Configurar toast según el tipo
  toast.className = 'toast';
  toast.classList.add(type);
  toast.innerHTML = `
    <div class="toast-icon">${getToastIcon(type)}</div>
    <div class="toast-content">
      <div class="toast-message">${message}</div>
    </div>
    <button class="toast-close" onclick="this.parentElement.classList.remove('show')">×</button>
  `;
  
  // Mostrar toast
  toast.classList.add('show');
  
  // Ocultar automáticamente después de la duración
  setTimeout(() => {
    toast.classList.remove('show');
  }, duration);
}

function getToastIcon(type) {
  const icons = {
    success: '✓',
    warning: '⚠',
    danger: '✕',
    info: 'ℹ'
  };
  return icons[type] || 'ℹ';
}

// Validación de formularios mejorada
function initFormValidation() {
  const forms = document.querySelectorAll('form[data-validate]');
  
  forms.forEach(form => {
    form.addEventListener('submit', function(e) {
      let isValid = true;
      const inputs = this.querySelectorAll('input[required], select[required], textarea[required]');
      
      inputs.forEach(input => {
        if (!input.value.trim()) {
          isValid = false;
          showInputError(input, 'Este campo es obligatorio');
        } else {
          clearInputError(input);
        }
      });
      
      if (!isValid) {
        e.preventDefault();
        showToast('Por favor, complete todos los campos obligatorios', 'warning');
      }
    });
  });
}

function showInputError(input, message) {
  const group = input.closest('.input-group') || input.parentElement;
  let errorElement = group.querySelector('.input-error');
  
  if (!errorElement) {
    errorElement = document.createElement('div');
    errorElement.className = 'input-error';
    group.appendChild(errorElement);
  }
  
  errorElement.textContent = message;
  input.classList.add('error');
}

function clearInputError(input) {
  const group = input.closest('.input-group') || input.parentElement;
  const errorElement = group.querySelector('.input-error');
  
  if (errorElement) {
    errorElement.remove();
  }
  
  input.classList.remove('error');
}

// Dashboard charts (si se usan)
function initDashboardCharts() {
  // Inicializar gráficos si existen
  const charts = document.querySelectorAll('[data-chart]');
  
  charts.forEach(chartElement => {
    const ctx = chartElement.getContext('2d');
    const type = chartElement.dataset.chartType || 'bar';
    const data = JSON.parse(chartElement.dataset.chartData || '{}');
    
    // Aquí puedes inicializar Chart.js si está disponible
    if (typeof Chart !== 'undefined') {
      new Chart(ctx, {
        type: type,
        data: data,
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom',
            }
          }
        }
      });
    }
  });
}

// Gestión de tabs
function initTabs() {
  const tabContainers = document.querySelectorAll('[data-tabs]');
  
  tabContainers.forEach(container => {
    const tabs = container.querySelectorAll('[data-tab]');
    const panes = container.querySelectorAll('[data-tab-pane]');
    
    tabs.forEach(tab => {
      tab.addEventListener('click', () => {
        const tabId = tab.dataset.tab;
        
        // Actualizar tabs activos
        tabs.forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        
        // Mostrar pane correspondiente
        panes.forEach(pane => {
          pane.classList.remove('active');
          if (pane.dataset.tabPane === tabId) {
            pane.classList.add('active');
          }
        });
      });
    });
  });
}

// Modal system
function initModals() {
  const modalTriggers = document.querySelectorAll('[data-modal-toggle]');
  const modals = document.querySelectorAll('[data-modal]');
  
  modalTriggers.forEach(trigger => {
    trigger.addEventListener('click', () => {
      const modalId = trigger.dataset.modalToggle;
      const modal = document.querySelector(`[data-modal="${modalId}"]`);
      
      if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
      }
    });
  });
  
  // Cerrar modales
  modals.forEach(modal => {
    const closeBtn = modal.querySelector('[data-modal-close]');
    if (closeBtn) {
      closeBtn.addEventListener('click', () => {
        modal.classList.remove('active');
        document.body.style.overflow = '';
      });
    }
    
    // Cerrar al hacer clic fuera
    modal.addEventListener('click', (e) => {
      if (e.target === modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
      }
    });
  });
}

// Inicialización cuando el DOM está listo
document.addEventListener('DOMContentLoaded', function() {
  initThemeToggle();
  initFormValidation();
  initDashboardCharts();
  initTabs();
  initModals();
  
  // Mostrar mensajes de URL parameters
  const urlParams = new URLSearchParams(window.location.search);
  const message = urlParams.get('msg');
  const type = urlParams.get('type') || 'info';
  
  if (message) {
    showToast(decodeURIComponent(message), type);
    
    // Limpiar URL
    const cleanUrl = window.location.pathname;
    window.history.replaceState({}, document.title, cleanUrl);
  }
});

// Funciones globales para usar en otros scripts
window.showToast = showToast;