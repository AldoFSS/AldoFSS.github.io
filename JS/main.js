document.addEventListener('DOMContentLoaded',() => {
    const openBtn = document.getElementById('open_cart')
    const cart = document.getElementById('sidecart')
    const closeBtn = document.getElementById('close_btn')
    const backdrop = document.querySelector('.backdrop')

    openBtn.addEventListener('click',openCart)
    closeBtn.addEventListener('click',closeCart)
    backdrop.addEventListener('click',closeCart)

    //Abre el menu despegable
    function openCart()
    {
        cart.classList.add('open')
        backdrop.classList.add('show')
    }
    //Cierre el menu despegable
    function closeCart()
    {
        cart.classList.remove('open')
        backdrop.classList.remove('show')
    }
})

