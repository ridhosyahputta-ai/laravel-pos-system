// Run with: node --test tests/Frontend/transaction-form.test.cjs
// Executes the actual inline Blade script with a small DOM event fixture.
// No browser submits, database access, dependency installs, or asset builds.
const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const view = fs.readFileSync(path.join(__dirname, '../../resources/views/transaksi/create.blade.php'), 'utf8');
const script = view.match(/<script>([\s\S]*?)<\/script>/)[1];

function fixture(prices = ['12500', '2500']) {
    const events = {};
    const pageEvents = {};
    const button = { disabled: true, textContent: 'Proses Transaksi' };
    const total = { textContent: '' };
    const inputs = prices.map(() => ({
        value: '1', max: '10', disabled: true, required: false, customError: '', nativeValid: true,
        attributes: {},
        setCustomValidity(value) { this.customError = value; },
        get validity() { return { valid: this.nativeValid && !this.customError }; },
        setAttribute(name, value) { this.attributes[name] = value; },
    }));
    const checks = prices.map((_, i) => ({ checked: false, dataset: { target: 'q' + i } }));
    const subtotals = prices.map(() => ({ textContent: '' }));
    const rows = prices.map((price, i) => ({
        dataset: { price },
        querySelector: selector => selector === '[data-subtotal]' ? subtotals[i] : checks[i],
    }));
    const form = {
        querySelectorAll: () => rows,
        addEventListener: (name, fn) => { events[name] = fn; },
        reportValidity() { this.validityReported = true; },
    };
    const elements = { 'sale-form': form, 'sale-submit': button, 'sale-total': total };
    inputs.forEach((input, i) => { elements['q' + i] = input; });
    vm.runInNewContext(script, {
        document: { getElementById: id => elements[id] },
        window: { addEventListener: (name, fn) => { pageEvents[name] = fn; } },
        Intl, BigInt,
    });
    return {
        inputs, checks, button, total, subtotals, events, pageEvents, form,
        choose(i, value = '1') { checks[i].checked = true; inputs[i].value = value; events.change(); },
        submit() {
            const event = { prevented: false, preventDefault() { this.prevented = true; } };
            events.submit(event);
            return event;
        },
    };
}

test('no product selected keeps save disabled and quantity inputs optional/disabled', () => {
    const f = fixture();
    assert.equal(f.button.disabled, true);
    assert.equal(f.total.textContent, 'Rp 0');
    assert.ok(f.inputs.every(input => input.disabled && !input.required));
    assert.equal(f.submit().prevented, true);
});

test('valid selected quantities enable save and calculate all subtotals', () => {
    const f = fixture();
    f.choose(0, '2'); f.choose(1, '3');
    assert.equal(f.button.disabled, false);
    assert.equal(f.inputs[0].required, true);
    assert.equal(f.inputs[0].disabled, false);
    assert.equal(f.subtotals[0].textContent, 'Rp 25.000');
    assert.equal(f.total.textContent, 'Rp 32.500');
});

for (const value of ['', '0', '-1', '1.5', '1e2', 'abc', '11', '99999999999']) {
    test(`invalid quantity ${JSON.stringify(value)} blocks save and shows no misleading estimate`, () => {
        const f = fixture();
        f.choose(0, value);
        assert.equal(f.button.disabled, true);
        assert.equal(f.subtotals[0].textContent, '—');
        assert.equal(f.total.textContent, 'Lengkapi jumlah beli yang valid');
        assert.equal(f.inputs[0].attributes['aria-invalid'], 'true');
        assert.notEqual(f.inputs[0].customError, '');
        assert.equal(f.submit().prevented, true);
        assert.equal(f.button.textContent, 'Proses Transaksi');
    });
}

test('one invalid line blocks the whole cart even when another line is valid', () => {
    const f = fixture(); f.choose(0, '2'); f.choose(1, '');
    assert.equal(f.subtotals[0].textContent, 'Rp 25.000');
    assert.equal(f.button.disabled, true);
    assert.equal(f.total.textContent, 'Lengkapi jumlah beli yang valid');
});

test('correcting an invalid value clears its error and enables save', () => {
    const f = fixture(); f.choose(0, ''); f.inputs[0].value = '10'; f.events.input();
    assert.equal(f.button.disabled, false);
    assert.equal(f.inputs[0].customError, '');
    assert.equal(f.inputs[0].attributes['aria-invalid'], 'false');
    assert.equal(f.total.textContent, 'Rp 125.000');
});

test('deselecting an invalid line clears its constraints and removes it from the total', () => {
    const f = fixture(); f.choose(0, '2'); f.choose(1, '');
    f.checks[1].checked = false; f.events.change();
    assert.equal(f.inputs[1].required, false);
    assert.equal(f.inputs[1].disabled, true);
    assert.equal(f.inputs[1].customError, '');
    assert.equal(f.total.textContent, 'Rp 25.000');
    assert.equal(f.button.disabled, false);
});

test('native browser invalidity is also respected', () => {
    const f = fixture(); f.inputs[0].nativeValid = false; f.choose(0, '2');
    assert.equal(f.button.disabled, true);
    assert.equal(f.submit().prevented, true);
});

test('zero-priced products and integer totals remain exact', () => {
    const f = fixture(['0', '2147483647']);
    f.choose(0, '2'); f.choose(1, '4');
    assert.equal(f.button.disabled, false);
    assert.equal(f.subtotals[0].textContent, 'Rp 0');
    assert.equal(f.total.textContent, 'Rp 8.589.934.588');
});

test('pending submission cannot be unlocked by edits, selection changes, or another submit', () => {
    const f = fixture(); f.choose(0, '2');
    assert.equal(f.submit().prevented, false);
    assert.equal(f.button.disabled, true);
    assert.equal(f.button.textContent, 'Menyimpan...');
    // Values stay enabled so the browser can serialize the original form fields.
    assert.equal(f.inputs[0].disabled, false);
    for (const value of ['', '3']) {
        f.inputs[0].value = value; f.events.input();
        assert.equal(f.button.disabled, true);
    }
    f.choose(1, '2');
    assert.equal(f.button.disabled, true);
    assert.equal(f.submit().prevented, true);
    assert.equal(f.button.textContent, 'Menyimpan...');
    f.pageEvents.pageshow({ persisted: false });
    assert.equal(f.button.disabled, true);
});

test('back-forward cache restoration resets submission state and revalidates the cart', () => {
    const f = fixture(); f.choose(0, '2'); f.submit();
    f.pageEvents.pageshow({ persisted: true });
    assert.equal(f.button.disabled, false);
    assert.equal(f.button.textContent, 'Proses Transaksi');
    f.submit(); f.inputs[0].value = '';
    f.pageEvents.pageshow({ persisted: true });
    assert.equal(f.button.disabled, true);
    assert.equal(f.total.textContent, 'Lengkapi jumlah beli yang valid');
});
