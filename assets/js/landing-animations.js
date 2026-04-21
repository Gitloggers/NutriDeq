window.addEventListener('load', () => {
    try {
        console.log("✅ landing-animations.js loaded!");

        gsap.registerPlugin(ScrollTrigger);

        // --- LENIS SMOOTH SCROLL ---
        const lenis = new Lenis({
            duration: 1.2,
            easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
            direction: 'vertical',
            smooth: true,
            mouseMultiplier: 1,
            touchMultiplier: 2,
        });

        lenis.on('scroll', ScrollTrigger.update);
        gsap.ticker.add((time) => {
            lenis.raf(time * 1000);
        });
        gsap.ticker.lagSmoothing(0);

        // --- THE "LITE-CLINICAL" SPLASH SCREEN ANIMATION ---
        const splashScreen = document.getElementById('splash-screen');
        const progressBar = document.querySelector('.splash-progress-bar');
        const splashPercent = document.querySelector('.splash-percent');
        const splashSymbol = document.querySelector('.splash-icon-symbol');
        const splashChars = document.querySelectorAll('.splash-char');
        const mainPage = document.querySelector('.page');
        
        const splashTimeline = gsap.timeline();

        // 1. Initial Staggered Intro
        splashTimeline
            .to(splashSymbol, { opacity: 1, scale: 1, duration: 1.2, ease: "expo.out" })
            .to(splashChars, { y: 0, opacity: 1, stagger: 0.05, duration: 1, ease: "expo.out" }, "-=0.8");

        // 2. Progress Logic
        let progress = { value: 0 };
        splashTimeline.to(progress, {
            value: 100,
            duration: 1.8,
            ease: "power2.inOut",
            onUpdate: () => {
                const val = Math.round(progress.value);
                splashPercent.innerHTML = val + "%";
                gsap.set(progressBar, { scaleX: val / 100 });
            }
        }, "-=0.2");

        // 3. ZOOM-IN FADE READY
        splashTimeline.addLabel("reveal", "+=0.1");
        
        // Final Exit sequence
        splashTimeline.to(splashScreen, { 
            scale: 1.2, 
            opacity: 0, 
            duration: 1.2, 
            ease: "expo.inOut" 
        }, "reveal");

        splashTimeline.to(mainPage, { 
            opacity: 1, 
            scale: 1, 
            filter: "blur(0px)", 
            duration: 1.5, 
            ease: "expo.out",
            onStart: () => {
                startHeroAnimations();
                initializeCounters();
            }
        }, "reveal+=0.3");

        splashTimeline.call(() => {
            splashScreen.style.display = 'none';
        });

        function startHeroAnimations() {
            const nav = document.getElementById('nav');
            const heroTimeline = gsap.timeline();

            heroTimeline
                .fromTo(nav, { y: -80, opacity: 0 }, { y: 0, opacity: 1, duration: 0.8, ease: "power3.out" })
                .fromTo(".hero-badge", { y: 20, opacity: 0 }, { y: 0, opacity: 1, duration: 0.6, ease: "power3.out" }, "-=0.4")
                .fromTo(".h1-word", { y: 40, opacity: 0 }, { y: 0, opacity: 1, duration: 0.8, stagger: 0.05, ease: "back.out(1.2)" }, "-=0.4")
                .fromTo(".hero-text p", { y: 30, opacity: 0 }, { y: 0, opacity: 1, duration: 0.7, ease: "power3.out" }, "-=0.5")
                .fromTo(".hero-cta .btn", { y: 20, opacity: 0, scale: 0.9 }, { y: 0, opacity: 1, scale: 1, duration: 0.6, ease: "back.out(1.4)" }, "-=0.4")
                .fromTo(".name-blend-grid .blend-card", { y: 30, opacity: 0 }, { y: 0, opacity: 1, stagger: 0.1, duration: 0.8, ease: "power3.out" }, "-=0.5")
                .fromTo(".model-card", { y: 50, opacity: 0, rotationY: 15, scale: 0.95 }, { y: 0, opacity: 1, rotationY: 0, scale: 1, duration: 1.2, ease: "expo.out" }, "-=0.7")
                .fromTo(".stat-card", { y: 40, opacity: 0 }, { y: 0, opacity: 1, duration: 0.8, stagger: 0.1, ease: "expo.out" }, "-=0.6")
                .fromTo(".scroll-indicator", { opacity: 0, y: -20 }, { opacity: 1, y: 0, duration: 1, ease: "power3.out" }, "-=0.5");

            // Hero scroll parallax
            gsap.to('.hero-left', { y: 100, opacity: 0, scrollTrigger: { trigger: '.hero', start: "top top", end: "bottom top", scrub: 1 } });
            gsap.to('.hero-right', { y: 120, opacity: 0, scrollTrigger: { trigger: '.hero', start: "top top", end: "bottom top", scrub: 1 } });
        }

        function initializeCounters() {
            const counters = document.querySelectorAll('[data-count]');
            counters.forEach(counter => {
                const target = parseInt(counter.getAttribute('data-count'));
                const suffix = counter.getAttribute('data-suffix') || '';
                let obj = { val: 0 };
                
                gsap.to(obj, {
                    val: target,
                    duration: 2.5,
                    ease: "power3.out",
                    scrollTrigger: {
                        trigger: counter,
                        start: "top 90%",
                        once: true
                    },
                    onUpdate: () => {
                        counter.innerHTML = Math.ceil(obj.val) + suffix;
                    }
                });
            });
            
            // Batch Reveals
            const glassCards = document.querySelectorAll('.feature-card, .step-card, .cap-card');
            gsap.set(glassCards, { y: 50, opacity: 0 });
            ScrollTrigger.batch(glassCards, {
                start: "top 90%",
                onEnter: batch => gsap.to(batch, { opacity: 1, y: 0, stagger: 0.1, duration: 1, ease: "power3.out", overwrite: "auto" })
            });

            // Tilt & 3D
            document.querySelectorAll('.feature-card, .model-card, .step-card, .cap-card, .blend-card, .stat-card').forEach(card => {
                card.addEventListener('mousemove', (e) => {
                    const rect = card.getBoundingClientRect();
                    const x = ((e.clientX - rect.left) / rect.width) - 0.5;
                    const y = ((e.clientY - rect.top) / rect.height) - 0.5;
                    gsap.to(card, { rotateX: y * -15, rotateY: x * 15, scale: 1.02, duration: 0.3, ease: "power2.out" });
                });
                card.addEventListener('mouseleave', () => {
                    gsap.to(card, { rotateX: 0, rotateY: 0, scale: 1, duration: 0.6, ease: "power3.out" });
                });
            });

            setTimeout(() => ScrollTrigger.refresh(), 500);
        }

        // --- INTERACTIVE PARTICLE CANVAS ---
        const particleContainer = document.getElementById('particles-container');
        if (particleContainer) {
            const canvas = document.createElement('canvas');
            canvas.style.position = 'absolute'; canvas.style.top = '0'; canvas.style.left = '0';
            canvas.style.width = '100%'; canvas.style.height = '100%'; canvas.style.pointerEvents = 'none';
            particleContainer.appendChild(canvas);
            const ctx = canvas.getContext('2d');
            let width, height, particles = [];
            const resize = () => {
                width = window.innerWidth; height = window.innerHeight;
                canvas.width = width; canvas.height = height;
            };
            window.addEventListener('resize', resize);
            resize();

            class Particle {
                constructor() {
                    this.x = Math.random() * width; this.y = Math.random() * height;
                    this.vx = (Math.random() - 0.5) * 0.3; this.vy = (Math.random() - 0.5) * 0.3;
                    this.radius = Math.random() * 2 + 1;
                }
                update() {
                    this.x += this.vx; this.y += this.vy;
                    if (this.x < 0 || this.x > width) this.vx = -this.vx;
                    if (this.y < 0 || this.y > height) this.vy = -this.vy;
                }
                draw() {
                    ctx.beginPath(); ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
                    ctx.fillStyle = "rgba(16, 185, 129, 0.3)"; ctx.fill();
                }
            }
            for (let i = 0; i < 30; i++) particles.push(new Particle());

            let mouse = { x: null, y: null };
            document.addEventListener('mousemove', e => { mouse.x = e.clientX; mouse.y = e.clientY; });

            function animateParticles() {
                ctx.clearRect(0, 0, width, height);
                particles.forEach(p => {
                    p.update(); p.draw();
                    if (mouse.x !== null) {
                        const dist = Math.hypot(mouse.x - p.x, mouse.y - p.y);
                        if (dist < 150) {
                            ctx.beginPath(); ctx.strokeStyle = `rgba(16, 185, 129, ${1 - dist/150})`;
                            ctx.lineWidth = 0.5; ctx.moveTo(p.x, p.y); ctx.lineTo(mouse.x, mouse.y); ctx.stroke();
                        }
                    }
                });
                requestAnimationFrame(animateParticles);
            }
            animateParticles();
        }

        // --- CUSTOM CURSOR ---
        const cursorDot = document.getElementById('cursor-dot');
        const cursorHighlight = document.getElementById('cursor-highlight');
        if (cursorDot && cursorHighlight) {
            document.addEventListener('mousemove', e => {
                gsap.to(cursorDot, { left: e.clientX, top: e.clientY, duration: 0.1 });
                gsap.to(cursorHighlight, { left: e.clientX, top: e.clientY, duration: 0.4 });
            });
            document.querySelectorAll('a, button, .feature-card, .step-card, .blend-card, .stat-card').forEach(el => {
                el.addEventListener('mouseenter', () => cursorDot.classList.add('active'));
                el.addEventListener('mouseleave', () => cursorDot.classList.remove('active'));
            });
        }

    } catch (error) {
        console.error("❌ landing-animations.js error:", error);
    }
});
