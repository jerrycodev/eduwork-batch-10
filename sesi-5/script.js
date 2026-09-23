// Add Icons
lucide.createIcons();

const listProductElement = document.getElementById("list-product");
const categoriesElement = document.getElementById("categories");
const searchInput = document.getElementById("search-input");

// categories
const categories = new Set([...products.map((product) => product.kategori)]);
categories.forEach((category) => {
  categoriesElement.innerHTML += `<option value="${category}">${category}</option>`;
});

categoriesElement.addEventListener("change", (event) => {
  const category = event.target.value;
  searchInput.value = "";
  if(category === "") return generateListProduct(products);
  generateListProduct(products.filter((product) => product.kategori === category));
});


// search-input
searchInput.addEventListener("input", (event) => {
  const searchText = event.target.value.toLowerCase();
  categoriesElement.value = "";
  if(searchText === "") return generateListProduct(products);
  generateListProduct(products.filter((product) => product.nama.toLowerCase().includes(searchText)));
});

// Generate List Product
document.addEventListener("DOMContentLoaded", () => {
  generateListProduct(products);
});