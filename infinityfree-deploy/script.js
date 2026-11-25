const SERVICES = ['General Consultation','Dental Checkup','Haircut','Counseling','Other'];
const form = document.getElementById('form');
const dateEl = document.getElementById('date');
const slotsEl = document.getElementById('slots');
const timeEl = document.getElementById('time');
const msgEl = document.getElementById('msg');
const btn = document.getElementById('btn');

// Fill services
document.getElementById('service').innerHTML =
  '<option value="">-- Choose --</option>' + SERVICES.map(s => `<option>${s}</option>`).join('');

// Block past dates
const today = new Date(); today.setMinutes(today.getMinutes() - today.getTimezoneOffset());
dateEl.min = today.toISOString().split('T')[0];

function show(text, ok) {
  msgEl.textContent = text;
  msgEl.className = 'msg ' + (ok ? 'ok' : 'err');
  msgEl.hidden = false;
  msgEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

async function loadSlots() {
  timeEl.value = '';
  const date = dateEl.value;
  if (!date) { slotsEl.innerHTML = '<em>Select a date first</em>'; return; }
  const day = new Date(date + 'T00:00').getDay();
  if (day === 0 || day === 6) { slotsEl.innerHTML = '<em>Closed on weekends. Pick a weekday.</em>'; return; }
  slotsEl.innerHTML = '<em>Loading...</em>';
  try {
    const res = await fetch('api/slots.php?date=' + encodeURIComponent(date));
    const data = await res.json();
    if (!res.ok) throw new Error(data.error);
    slotsEl.innerHTML = '';
    data.forEach(s => {
      const b = document.createElement('button');
      b.type = 'button';
      b.className = 'slot';
      b.textContent = fmt(s.time);
      b.disabled = !s.available;
      b.onclick = () => {
        document.querySelectorAll('.slot').forEach(x => x.classList.remove('selected'));
        b.classList.add('selected');
        timeEl.value = s.time;
      };
      slotsEl.appendChild(b);
    });
  } catch (e) { slotsEl.innerHTML = '<em>Could not load times.</em>'; }
}
function fmt(t) {
  const [h, m] = t.split(':'); const hr = +h;
  return `${hr % 12 || 12}:${m} ${hr >= 12 ? 'PM' : 'AM'}`;
}
dateEl.addEventListener('change', loadSlots);

form.addEventListener('submit', async e => {
  e.preventDefault();
  msgEl.hidden = true;
  const data = Object.fromEntries(new FormData(form));
  if (!data.name.trim() || !data.email || !data.phone || !data.service || !data.date)
    return show('Please fill in all required fields.', false);
  if (!data.time) return show('Please select a time slot.', false);

  btn.disabled = true; btn.textContent = 'Booking...';
  try {
    const res = await fetch('api/book.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });
    const out = await res.json();
    if (!res.ok) { show(out.error || 'Booking failed.', false); loadSlots(); }
    else { show(`Booked! Your reference number is #${out.id}. Status: pending confirmation.`, true); form.reset(); slotsEl.innerHTML = '<em>Select a date first</em>'; }
  } catch (err) { show('Network error. Try again.', false); }
  btn.disabled = false; btn.textContent = 'Book Appointment';
});
