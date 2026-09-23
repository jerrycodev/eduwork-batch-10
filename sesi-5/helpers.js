function formatRupiah(harga) {
  return harga.toLocaleString("id-ID", {
    style: "currency",
    currency: "IDR",
    minimumFractionDigits: 0,
  });
}

function generateListProduct(products) {
  listProductElement.innerHTML = "";
  if(products.length === 0) {
    listProductElement.innerHTML = `<div class="flex justify-center items-center sm:col-span-2 md:col-span-3 h-[200px] border-2 border-dashed border-gray-50 rounded-lg bg-white">
      <p class="text-center">Tidak ada produk yang ditemukan</p>
    </div>`;
    return
  };
  products.forEach((product) => {
    const { id, nama, harga, gambar, kategori, deskripsi } = product;
    listProductElement.innerHTML += `<article
          class="flex flex-col justify-center p-4 bg-white rounded-lg"
        >
          <img
            src="${gambar}"
            alt="${nama}"
            class="w-full h-auto mb-4 object-cover rounded-lg aspect-square"
          />
          <h2 class="text-xl font-bold">${nama}</h2>
          <p class="text-sm">${formatRupiah(harga)}</p>
        </article>`;
  });
}