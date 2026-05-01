/**
 * Nexus Notes - Ethiopian Calendar Module
 * Handles Ethiopian calendar conversion and display
 */

const Calendar = {
    // Ethiopian month names in Amharic (transliterated)
    ethiopianMonths: [
        'Meskerem', 'Tikimt', 'Hidar', 'Tahsas', 
        'Tir', 'Yekatit', 'Megabit', 'Miyazia', 
        'Ginbot', 'Sene', 'Hamle', 'Nehase', 'Pagumen'
    ],

    // Ethiopian month names in Amharic script
    ethiopianMonthsAmharic: [
        'መስከረም', 'ጥቅምት', 'ህዳር', 'ታህሳስ',
        'ጥር', 'የካቲት', 'መጋቢት', 'ሚያዝያ',
        'ግንቦት', 'ሰኔ', 'ሐምሌ', 'ነሐሴ', 'ጳጉሜ'
    ],

    /**
     * Convert Gregorian date to Ethiopian date
     */
    toEthiopian(date = null) {
        if (!date) date = new Date();
        
        const gregorianYear = date.getFullYear();
        const gregorianMonth = date.getMonth() + 1;
        const gregorianDay = date.getDate();
        
        // Calculate Julian Day Number
        const jd = this.gregorianToJulian(gregorianYear, gregorianMonth, gregorianDay);
        
        // Ethiopian epoch in Julian Days (August 27, 8 AD Julian)
        const ethiopianEpoch = 1724220;
        
        // Days since Ethiopian epoch
        const daysSinceEpoch = jd - ethiopianEpoch;
        
        // Ethiopian year (approximately)
        let ethiopianYear = Math.floor(daysSinceEpoch / 365.25);
        
        // Adjust for the fact that Ethiopian year starts in September
        if (gregorianMonth < 9) {
            ethiopianYear--;
        }
        
        // Ethiopian year in their era (8 years behind + 5509 from creation)
        ethiopianYear += 8;
        
        // Calculate remaining days for month/day
        let remainingDays = daysSinceEpoch - Math.floor(ethiopianYear * 365.25);
        if (remainingDays < 0) remainingDays += 365;
        
        // Ethiopian months are 30 days each, plus 5-6 epagomenal days
        let ethiopianMonth = Math.floor(remainingDays / 30);
        let ethiopianDay = Math.floor(remainingDays % 30) + 1;
        
        // Handle Pagumen (13th month)
        if (ethiopianMonth >= 13) {
            ethiopianMonth = 12;
            ethiopianDay = Math.min(ethiopianDay, this.isEthiopianLeapYear(ethiopianYear) ? 6 : 5);
        }
        
        return {
            year: ethiopianYear,
            month: ethiopianMonth,
            day: ethiopianDay,
            monthName: this.ethiopianMonths[ethiopianMonth - 1],
            monthNameAmharic: this.ethiopianMonthsAmharic[ethiopianMonth - 1],
            formatted: `${this.ethiopianMonths[ethiopianMonth - 1]} ${ethiopianDay}, ${ethiopianYear}`,
            formattedAmharic: `${this.ethiopianMonthsAmharic[ethiopianMonth - 1]} ${ethiopianDay}, ${ethiopianYear}`
        };
    },

    /**
     * Convert Ethiopian date to Gregorian
     */
    toGregorian(ethiopianYear, ethiopianMonth, ethiopianDay) {
        // Ethiopian epoch
        const ethiopianEpoch = 1724220;
        
        // Days from complete years
        let totalDays = Math.floor((ethiopianYear - 8) * 365.25);
        
        // Days from complete months
        totalDays += (ethiopianMonth - 1) * 30;
        
        // Days from current month
        totalDays += ethiopianDay - 1;
        
        // Julian Day Number
        const jd = ethiopianEpoch + totalDays;
        
        // Convert to Gregorian
        return this.julianToGregorian(jd);
    },

    /**
     * Check if Ethiopian year is a leap year
     */
    isEthiopianLeapYear(year) {
        // Ethiopian leap year cycle: every 4 years
        return ((year + 1) % 4) === 0;
    },

    /**
     * Check if Gregorian year is a leap year
     */
    isGregorianLeapYear(year) {
        return (year % 4 === 0 && year % 100 !== 0) || (year % 400 === 0);
    },

    /**
     * Convert Gregorian date to Julian Day Number
     */
    gregorianToJulian(year, month, day) {
        const a = Math.floor((14 - month) / 12);
        const y = year + 4800 - a;
        const m = month + 12 * a - 3;
        
        return day + Math.floor((153 * m + 2) / 5) + 365 * y + Math.floor(y / 4) - Math.floor(y / 100) + Math.floor(y / 400) - 32045;
    },

    /**
     * Convert Julian Day Number to Gregorian date
     */
    julianToGregorian(jd) {
        const a = jd + 32044;
        const b = Math.floor((4 * a + 3) / 146097);
        const c = a - Math.floor((146097 * b) / 4);
        const d = Math.floor((4 * c + 3) / 1461);
        const e = c - Math.floor((1461 * d) / 4);
        const m = Math.floor((5 * e + 2) / 153);
        
        return {
            day: e - Math.floor((153 * m + 2) / 5) + 1,
            month: m + 3 - 12 * Math.floor(m / 10),
            year: 100 * b + d - 4800 + Math.floor(m / 10)
        };
    },

    /**
     * Get current time in specific UTC offset
     */
    getTimeForOffset(offsetHours) {
        const now = new Date();
        const utc = now.getTime() + (now.getTimezoneOffset() * 60000);
        const adjusted = new Date(utc + (3600000 * offsetHours));
        
        return {
            hours: adjusted.getHours().toString().padStart(2, '0'),
            minutes: adjusted.getMinutes().toString().padStart(2, '0'),
            seconds: adjusted.getSeconds().toString().padStart(2, '0'),
            formatted: `${adjusted.getHours().toString().padStart(2, '0')}:${adjusted.getMinutes().toString().padStart(2, '0')}:${adjusted.getSeconds().toString().padStart(2, '0')}`
        };
    },

    /**
     * Get dual calendar display data
     */
    getDualDisplay() {
        const now = new Date();
        const ethiopian = this.toEthiopian(now);
        const gregorian = {
            day: now.getDate(),
            month: now.toLocaleString('default', { month: 'long' }),
            year: now.getFullYear(),
            formatted: now.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            })
        };
        
        return {
            ethiopian,
            gregorian,
            utc9: this.getTimeForOffset(9),
            utc3: this.getTimeForOffset(3),
            timestamp: Math.floor(now.getTime() / 1000)
        };
    },

    /**
     * Update all calendar displays on page
     */
    updateDisplays() {
        const displays = document.querySelectorAll('[data-calendar-display]');
        const data = this.getDualDisplay();
        
        displays.forEach(display => {
            const type = display.dataset.calendarType;
            
            switch(type) {
                case 'ethiopian':
                    display.textContent = data.ethiopian.formatted;
                    break;
                case 'gregorian':
                    display.textContent = data.gregorian.formatted;
                    break;
                case 'utc9':
                    display.textContent = data.utc9.formatted;
                    break;
                case 'utc3':
                    display.textContent = data.utc3.formatted;
                    break;
                case 'full':
                    display.innerHTML = `
                        <div class="text-sm">
                            <div class="font-medium">${data.ethiopian.formatted}</div>
                            <div class="text-xs text-gray-500">${data.gregorian.formatted}</div>
                        </div>
                        <div class="text-xs font-mono">
                            <div>UTC+9: ${data.utc9.formatted}</div>
                            <div>UTC+3: ${data.utc3.formatted}</div>
                        </div>
                    `;
                    break;
            }
        });
    },

    /**
     * Start live clock updates
     */
    startLiveClock(selector) {
        const element = document.querySelector(selector);
        if (!element) return;
        
        const updateClock = () => {
            const data = this.getDualDisplay();
            element.innerHTML = `
                <div class="grid grid-cols-2 gap-4 text-xs">
                    <div>
                        <div class="font-medium text-gray-700 dark:text-gray-300">${data.ethiopian.formatted}</div>
                        <div class="text-gray-500">${data.gregorian.formatted}</div>
                    </div>
                    <div class="font-mono">
                        <div>UTC+9: ${data.utc9.formatted}</div>
                        <div>UTC+3: ${data.utc3.formatted}</div>
                    </div>
                </div>
            `;
        };
        
        updateClock();
        setInterval(updateClock, 1000);
    }
};

// Make available globally
window.Calendar = Calendar;

// Auto-update on load
document.addEventListener('DOMContentLoaded', () => {
    Calendar.updateDisplays();
    setInterval(() => Calendar.updateDisplays(), 1000);
});
