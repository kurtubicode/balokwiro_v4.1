// =======================================================
// ==       SCRIPT.JS - VERSI FINAL GABUNGAN          ==
// =======================================================

// --- BAGIAN 1: FUNGSI-FUNGSI UTAMA UNTUK KERANJANG BELANJA ---

/**
 * Menambahkan produk ke keranjang setelah memvalidasi stok melalui API.
 * @param {object} product - Objek produk dengan {id, nama, harga}.
 */
async function addToCart(product) {
    let cart = JSON.parse(localStorage.getItem('kueBalokCart')) || {};
    const currentQuantityInCart = cart[product.id] ? cart[product.id].jumlah : 0;

    try {
        const response = await fetch(`cek_stok.php?id=${product.id}`);
        const data = await response.json();

        if (data.error) {
            alert(data.message); // Menampilkan pesan error dari server
            return;
        }

        const serverStock = data.stok;

        if (serverStock > currentQuantityInCart) {
            if (cart[product.id]) {
                cart[product.id].jumlah++;
            } else {
                cart[product.id] = {
                    nama: product.nama,
                    harga: product.harga,
                    jumlah: 1
                };
            }
            localStorage.setItem('kueBalokCart', JSON.stringify(cart));
            alert(`'${product.nama}' berhasil ditambahkan ke keranjang!`);
            updateCartIcon();
        } else {
            alert(`Maaf, stok untuk '${product.nama}' tidak mencukupi (sisa: ${serverStock}).`);
        }
    } catch (error) {
        console.error('Error saat addToCart:', error);
        alert('Terjadi kesalahan saat memeriksa stok.');
    }
}

/**
 * Mengupdate jumlah item di halaman keranjang, termasuk validasi stok saat menambah.
 * @param {string} productId - ID produk yang akan diupdate.
 * @param {number} change - Jumlah perubahan (+1 atau -1).
 */
async function updateCartQuantity(productId, change) {
    let cart = JSON.parse(localStorage.getItem('kueBalokCart')) || {};
    if (!cart[productId]) return;

    // Jika user menambah jumlah (+1), kita perlu cek stok lagi
    if (change > 0) {
        try {
            const response = await fetch(`cek_stok.php?id=${productId}`);
            const data = await response.json();
            if (data.error || data.stok <= cart[productId].jumlah) {
                alert(`Maaf, stok untuk '${cart[productId].nama}' tidak mencukupi.`);
                return; // Batalkan penambahan jika stok tidak cukup
            }
        } catch (error) {
            console.error('Error saat updateCartQuantity:', error);
            alert('Gagal memverifikasi stok.');
            return;
        }
    }

    // Lanjutkan update jika stok aman atau jika user mengurangi jumlah
    cart[productId].jumlah += change;

    // Hapus item jika jumlahnya 0 atau kurang
    if (cart[productId].jumlah <= 0) {
        delete cart[productId];
    }

    localStorage.setItem('kueBalokCart', JSON.stringify(cart));
    renderCartPage(); // Gambar ulang tabel keranjang
    updateCartIcon(); // Update ikon
}

/**
 * Menghapus item dari keranjang.
 * @param {string} productId - ID produk yang akan dihapus.
 */
function removeFromCart(productId) {
    let cart = JSON.parse(localStorage.getItem('kueBalokCart')) || {};
    if (cart[productId] && confirm(`Anda yakin ingin menghapus '${cart[productId].nama}' dari keranjang?`)) {
        delete cart[productId];
        localStorage.setItem('kueBalokCart', JSON.stringify(cart));
        renderCartPage();
        updateCartIcon();
    }
}

/**
 * Mengupdate angka pada ikon keranjang belanja.
 */
function updateCartIcon() {
    const cart = JSON.parse(localStorage.getItem('kueBalokCart')) || {};
    let totalItems = 0;
    for (const id in cart) {
        totalItems += cart[id].jumlah;
    }

    const cartCountElement = document.querySelector('.cart-item-count');
    if (cartCountElement) {
        cartCountElement.textContent = totalItems;
        cartCountElement.style.display = totalItems > 0 ? 'inline-block' : 'none';
    }
}

/**
 * Menampilkan semua item dari keranjang ke dalam tabel di halaman keranjang.php.
 */
function renderCartPage() {
    const cart = JSON.parse(localStorage.getItem('kueBalokCart')) || {};
    const cartContainer = document.getElementById('cart-items-container');
    const totalPriceElement = document.getElementById('cart-total-price');
    const cartDataInput = document.getElementById('cart-data-input');
    const checkoutButton = document.querySelector('#checkout-form button[type="submit"]');
    let totalPrice = 0;

    if (!cartContainer) return; // Keluar jika bukan di halaman keranjang

    cartContainer.innerHTML = ''; 

    if (Object.keys(cart).length === 0) {
        cartContainer.innerHTML = '<tr><td colspan="5" style="text-align:center; padding: 2rem;">Keranjang Anda masih kosong.</td></tr>';
        if (totalPriceElement) totalPriceElement.textContent = 'Total: Rp 0';
        if (checkoutButton) checkoutButton.disabled = true;
        return;
    }

    if (checkoutButton) checkoutButton.disabled = false;

    for (const id in cart) {
        const item = cart[id];
        const subtotal = item.harga * item.jumlah;
        totalPrice += subtotal;

        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${item.nama}</td>
            <td>Rp ${item.harga.toLocaleString('id-ID')}</td>
            <td>
                <div class="quantity-control">
                    <button class="quantity-btn" data-id="${id}" data-change="-1">-</button>
                    <input type="text" value="${item.jumlah}" readonly>
                    <button class="quantity-btn" data-id="${id}" data-change="1">+</button>
                </div>
            </td>
            <td class="text-end">Rp ${subtotal.toLocaleString('id-ID')}</td>
            <td class="text-center"><a href="#" class="remove-btn" data-id="${id}">Hapus</a></td>
        `;
        cartContainer.appendChild(row);
    }

    if (totalPriceElement) totalPriceElement.textContent = `Total: Rp ${totalPrice.toLocaleString('id-ID')}`;
    if (cartDataInput) cartDataInput.value = JSON.stringify(cart);
}


// --- BAGIAN 2: PUSAT INISIALISASI HALAMAN & EVENT LISTENER ---

document.addEventListener('DOMContentLoaded', function () {
    
    // Inisialisasi UI Umum (Navbar, dll.)
    const navbarNav = document.querySelector('.navbar-nav');
    const hamburgerMenu = document.querySelector('#hamburger-menu');
    if (hamburgerMenu) {
        hamburgerMenu.onclick = (e) => {
            navbarNav.classList.toggle('active');
            e.preventDefault();
        };
    }
    document.addEventListener('click', function (e) {
        if (hamburgerMenu && !hamburgerMenu.contains(e.target) && !navbarNav.contains(e.target)) {
            navbarNav.classList.remove('active');
        }
    });

    // Inisialisasi Keranjang Belanja (berjalan di semua halaman)
    updateCartIcon();

    // Inisialisasi KHUSUS untuk Halaman yang ada Kartu Menu
    if (document.querySelector('.menu-card')) {
        const addToCartButtons = document.querySelectorAll('.add-to-cart-btn button');
        addToCartButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const product = {
                    id: this.dataset.id,
                    nama: this.dataset.nama,
                    harga: parseInt(this.dataset.harga)
                };
                addToCart(product);
            });
        });
    }
    
    // Inisialisasi KHUSUS untuk Halaman Keranjang
    if (document.getElementById('cart-items-container')) {
        renderCartPage();

        // Event listener untuk tombol +/- dan Hapus di halaman keranjang
        document.getElementById('cart-items-container').addEventListener('click', function(e) {
            e.preventDefault();
            // Tombol +/-
            if (e.target.classList.contains('quantity-btn')) {
                const id = e.target.dataset.id;
                const change = parseInt(e.target.dataset.change);
                updateCartQuantity(id, change);
            }
            // Tombol Hapus
            if (e.target.classList.contains('remove-btn')) {
                const id = e.target.dataset.id;
                removeFromCart(id);
            }
        });
    }
    
    // Logika untuk form checkout di halaman keranjang.php
    const checkoutForm = document.getElementById('checkout-form');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(e) {
            const cart = JSON.parse(localStorage.getItem('kueBalokCart')) || {};
            if (Object.keys(cart).length === 0) {
                alert('Keranjang Anda kosong! Silakan pilih menu terlebih dahulu.');
                return;
            }
            document.getElementById('cart-data-input').value = JSON.stringify(cart);
            localStorage.removeItem('kueBalokCart');
        });
    }

    // Inisialisasi Ikon Feather (jika Anda menggunakannya)
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
});