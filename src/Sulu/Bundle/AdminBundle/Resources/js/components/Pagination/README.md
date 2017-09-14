A simple pagination taking props for the current page, the total number of pages and a callback, which gets called
when the page is being changed. The callback also receives the new page.

    <div style={{height: '40px'}}>
        <Pagination current={5} total={10} onChange={(page) => alert('The new page number is ' + page)} />
    </div>
