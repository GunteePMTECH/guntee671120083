// background.js

const canvas = document.createElement('canvas');
document.body.prepend(canvas); // ใส่ canvas ไว้หน้าสุดของ body

const ctx = canvas.getContext('2d');
canvas.id = 'neural-canvas';

// ตั้งค่าสไตล์ของ Canvas ให้เต็มจอและอยู่หลังสุด
canvas.style.position = 'fixed';
canvas.style.top = '0';
canvas.style.left = '0';
canvas.style.width = '100%';
canvas.style.height = '100%';
canvas.style.zIndex = '-1'; // อยู่หลัง content อื่นๆ
// canvas.style.background = '#0d1117'; // (ถ้าต้องการให้ canvas มีสีพื้นหลังเอง แต่ตอนนี้ใช้ของ body ดีกว่า)

let width, height;
let particles = [];
let mouse = { x: null, y: null, radius: 150 }; // รัศมีที่เมาส์ส่งผลกระทบ

// การตั้งค่าเส้น
const config = {
    particleColor: 'rgba(0, 195, 255, 0.8)', // สีจุด (ฟ้าสว่าง)
    lineColor: 'rgba(0, 150, 255,', // สีเส้น (ส่วนท้ายจะเติม opacity ทีหลัง)
    lineWidth: 1.5,
    shadowColor: '#0096ff', // สีเงา neon
    shadowBlur: 15, // ความฟุ้งของเงา
    particleCount: window.innerWidth / 10, // จำนวนจุดเริ่มต้นตามความกว้างหน้าจอ
    connectionDistance: 120, // ระยะที่จะให้เส้นเชื่อมกัน
    speed: 0.5 // ความเร็วการเคลื่อนที่ของจุด
};

// อัปเดตขนาด canvas
function resize() {
    width = canvas.width = window.innerWidth;
    height = canvas.height = window.innerHeight;
}

window.addEventListener('resize', resize);
resize();

// ติดตามตำแหน่งเมาส์
window.addEventListener('mousemove', (e) => {
    mouse.x = e.x;
    mouse.y = e.y;
});

// เมาส์ออกนอกจอ
window.addEventListener('mouseout', () => {
    mouse.x = undefined;
    mouse.y = undefined;
});

// *** คลิกเพื่อเพิ่มจุด ***
window.addEventListener('click', (e) => {
    // เพิ่มจุดใหม่ตรงตำแหน่งที่คลิก
    particles.push(new Particle(e.x, e.y));
    // (Optional) ถ้าไม่อยากให้เยอะเกินไป อาจจะจำกัดจำนวนสูงสุด
    // if (particles.length > 300) { particles.shift(); } 
});


// คลาสสำหรับจุด (Particle)
class Particle {
    constructor(x, y) {
        // ถ้ามีการส่ง x, y มา (จากการคลิก) ให้ใช้ค่านั้น ถ้าไม่มีให้สุ่ม
        this.x = x || Math.random() * width;
        this.y = y || Math.random() * height;
        this.radius = Math.random() * 2 + 1; // ขนาดจุด
        this.speedX = (Math.random() - 0.5) * config.speed;
        this.speedY = (Math.random() - 0.5) * config.speed;
    }

    update() {
        // เคลื่อนที่
        this.x += this.speedX;
        this.y += this.speedY;

        // ชนขอบจอแล้วเด้งกลับ
        if (this.x < 0 || this.x > width) this.speedX *= -1;
        if (this.y < 0 || this.y > height) this.speedY *= -1;

        // ปฏิสัมพันธ์กับเมาส์ (หนีเมาส์)
        if (mouse.x) {
            let dx = mouse.x - this.x;
            let dy = mouse.y - this.y;
            let distance = Math.sqrt(dx * dx + dy * dy);
            if (distance < mouse.radius) {
                if (mouse.x < this.x && this.x < width - 10) this.x += 2;
                if (mouse.x > this.x && this.x > 0 + 10) this.x -= 2;
                if (mouse.y < this.y && this.y < height - 10) this.y += 2;
                if (mouse.y > this.y && this.y > 0 + 10) this.y -= 2;
            }
        }
    }

    draw() {
        ctx.beginPath();
        ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
        ctx.fillStyle = config.particleColor;
        // เพิ่มความเงาให้จุด
        ctx.shadowColor = config.shadowColor;
        ctx.shadowBlur = config.shadowBlur;
        ctx.fill();
        // รีเซ็ต shadow เพื่อไม่ให้กระทบ performance มากเกินไป (หรือกระทบ object อื่นถ้ามี)
        ctx.shadowBlur = 0; 
    }
}

// สร้างจุดเริ่มต้น
function init() {
    particles = [];
    for (let i = 0; i < config.particleCount; i++) {
        particles.push(new Particle());
    }
}

// ฟังก์ชันวาดเส้นเชื่อม
function connect() {
    let opacityValue = 1;
    for (let a = 0; a < particles.length; a++) {
        for (let b = a; b < particles.length; b++) {
            let distance = ((particles[a].x - particles[b].x) * (particles[a].x - particles[b].x)) +
                           ((particles[a].y - particles[b].y) * (particles[a].y - particles[b].y));
            
            // ถ้าระยะใกล้กันพอ ให้วาดเส้น
            if (distance < (config.connectionDistance * config.connectionDistance)) {
                // คำนวณความจางของเส้นตามระยะทาง (ยิ่งไกลยิ่งจาง)
                opacityValue = 1 - (distance / (config.connectionDistance * config.connectionDistance));
                
                ctx.beginPath();
                ctx.strokeStyle = config.lineColor + opacityValue + ')';
                ctx.lineWidth = config.lineWidth;
                
                // เพิ่มความเงาให้เส้น
                ctx.shadowColor = config.shadowColor;
                ctx.shadowBlur = config.shadowBlur;
                
                ctx.moveTo(particles[a].x, particles[a].y);
                ctx.lineTo(particles[b].x, particles[b].y);
                ctx.stroke();
                ctx.closePath();
                
                 // รีเซ็ต shadow 
                ctx.shadowBlur = 0;
            }
        }
    }
}

// ลูปการทำงาน
function animate() {
    requestAnimationFrame(animate);
    ctx.clearRect(0, 0, width, height); // ล้าง canvas ก่อนวาดใหม่

    for (let i = 0; i < particles.length; i++) {
        particles[i].update();
        particles[i].draw();
    }
    connect();
}

// เริ่มทำงาน
init();
animate();