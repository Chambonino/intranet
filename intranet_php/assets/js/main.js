/**
 * JavaScript Principal - Intranet Corporativa
 */

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar componentes
    initSlider();
    initCalendar();
    initGallerySlider();
    initVideoSlider();
    initCountdown();
    initDepartmentTabs();
});

/**
 * Slider de Noticias
 */
function initSlider() {
    const slides = document.querySelectorAll('.slide');
    const dots = document.querySelectorAll('.dot');
    const prevBtn = document.querySelector('.slider-btn.prev');
    const nextBtn = document.querySelector('.slider-btn.next');
    
    if (slides.length === 0) return;
    
    let currentSlide = 0;
    let slideInterval;
    
    function showSlide(index) {
        slides.forEach(slide => slide.classList.remove('active'));
        dots.forEach(dot => dot.classList.remove('active'));
        
        if (index >= slides.length) currentSlide = 0;
        if (index < 0) currentSlide = slides.length - 1;
        
        slides[currentSlide].classList.add('active');
        if (dots[currentSlide]) dots[currentSlide].classList.add('active');
    }
    
    function nextSlide() {
        currentSlide++;
        showSlide(currentSlide);
    }
    
    function prevSlide() {
        currentSlide--;
        showSlide(currentSlide);
    }
    
    // Auto-slide
    function startAutoSlide() {
        slideInterval = setInterval(nextSlide, 5000);
    }
    
    function stopAutoSlide() {
        clearInterval(slideInterval);
    }
    
    // Event listeners
    if (nextBtn) nextBtn.addEventListener('click', () => { nextSlide(); stopAutoSlide(); startAutoSlide(); });
    if (prevBtn) prevBtn.addEventListener('click', () => { prevSlide(); stopAutoSlide(); startAutoSlide(); });
    
    dots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
            currentSlide = index;
            showSlide(currentSlide);
            stopAutoSlide();
            startAutoSlide();
        });
    });
    
    // Iniciar
    showSlide(0);
    startAutoSlide();
}

/**
 * Calendario
 */
function initCalendar() {
    const calendarContainer = document.querySelector('.calendar-grid');
    if (!calendarContainer) return;
    
    const now = new Date();
    const year = now.getFullYear();
    const month = now.getMonth();
    
    const monthNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
                        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    const dayNames = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
    
    // Header del calendario
    const calendarHeader = document.querySelector('.calendar-header');
    if (calendarHeader) {
        calendarHeader.innerHTML = `<h3>${monthNames[month]} ${year}</h3>`;
    }
    
    // Días de la semana
    calendarContainer.innerHTML = '';
    dayNames.forEach(day => {
        calendarContainer.innerHTML += `<div class="calendar-day-header">${day}</div>`;
    });
    
    // Primer día del mes
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const today = now.getDate();
    
    // Días vacíos antes del mes
    for (let i = 0; i < firstDay; i++) {
        calendarContainer.innerHTML += '<div class="calendar-day"></div>';
    }
    
    // Días del mes
    for (let day = 1; day <= daysInMonth; day++) {
        const isToday = day === today ? 'today' : '';
        const hasEvent = eventDates && eventDates.includes(day) ? 'has-event' : '';
        calendarContainer.innerHTML += `<div class="calendar-day ${isToday} ${hasEvent}">${day}</div>`;
    }
}

// Variable global para fechas de eventos (se llena desde PHP)
let eventDates = [];

/**
 * Slider de Galería
 */
function initGallerySlider() {
    const track = document.querySelector('.gallery-track');
    const prevBtn = document.querySelector('.gallery-prev');
    const nextBtn = document.querySelector('.gallery-next');
    
    if (!track) return;
    
    let position = 0;
    const itemWidth = 265; // 250px + 15px margin
    const items = track.querySelectorAll('.gallery-item');
    const maxPosition = -(items.length * itemWidth - track.parentElement.offsetWidth);
    
    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            position = Math.max(position - itemWidth * 3, maxPosition);
            track.style.transform = `translateX(${position}px)`;
        });
    }
    
    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            position = Math.min(position + itemWidth * 3, 0);
            track.style.transform = `translateX(${position}px)`;
        });
    }
}

/**
 * Slider de Videos
 */
function initVideoSlider() {
    const track = document.querySelector('.video-track');
    if (!track) return;
    
    // Scroll horizontal con rueda del mouse
    track.addEventListener('wheel', (e) => {
        e.preventDefault();
        track.scrollLeft += e.deltaY;
    });
}

/**
 * Cuenta Regresiva
 */
function initCountdown() {
    const countdownElement = document.querySelector('.countdown-timer');
    if (!countdownElement) return;
    
    const targetDate = countdownElement.dataset.target;
    if (!targetDate) return;
    
    const target = new Date(targetDate).getTime();
    
    function updateCountdown() {
        const now = new Date().getTime();
        const distance = target - now;
        
        if (distance < 0) {
            countdownElement.innerHTML = '<p>¡El evento ha comenzado!</p>';
            return;
        }
        
        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
        
        document.getElementById('countdown-days').textContent = days;
        document.getElementById('countdown-hours').textContent = hours;
        document.getElementById('countdown-minutes').textContent = minutes;
        document.getElementById('countdown-seconds').textContent = seconds;
    }
    
    updateCountdown();
    setInterval(updateCountdown, 1000);
}

/**
 * Tabs de Departamentos
 */
function initDepartmentTabs() {
    const tabs = document.querySelectorAll('.dept-tab');
    const filesContainer = document.querySelector('.files-list');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            const deptId = this.dataset.dept;
            loadDepartmentFiles(deptId);
        });
    });
}

/**
 * Cargar archivos por departamento via AJAX
 */
function loadDepartmentFiles(deptId) {
    const container = document.querySelector('.files-list');
    if (!container) return;
    
    container.innerHTML = '<p style="text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Cargando...</p>';
    
    fetch(`api/get_files.php?dept=${deptId}`)
        .then(response => response.json())
        .then(data => {
            if (data.length === 0) {
                container.innerHTML = '<p style="text-align: center; padding: 20px; color: #666;">No hay archivos disponibles</p>';
                return;
            }
            
            let html = '';
            data.forEach(file => {
                const iconClass = getFileIcon(file.archivo);
                html += `
                    <div class="file-item">
                        <div class="file-info">
                            <div class="file-icon ${iconClass}">
                                <i class="fas fa-file"></i>
                            </div>
                            <div>
                                <strong>${file.nombre}</strong>
                                <p style="font-size: 0.85rem; color: #666;">${file.descripcion || ''}</p>
                            </div>
                        </div>
                        <a href="download.php?id=${file.id}" class="download-btn">
                            <i class="fas fa-download"></i> Descargar
                        </a>
                    </div>
                `;
            });
            container.innerHTML = html;
        })
        .catch(error => {
            container.innerHTML = '<p style="text-align: center; padding: 20px; color: #c62828;">Error al cargar archivos</p>';
        });
}

/**
 * Obtener icono según tipo de archivo
 */
function getFileIcon(filename) {
    const ext = filename.split('.').pop().toLowerCase();
    const icons = {
        'pdf': 'pdf',
        'doc': 'doc',
        'docx': 'doc',
        'xls': 'xls',
        'xlsx': 'xls'
    };
    return icons[ext] || '';
}

/**
 * Confirmar eliminación
 */
function confirmDelete(message) {
    return confirm(message || '¿Está seguro de eliminar este registro?');
}

/**
 * Preview de imagen antes de subir
 */
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
