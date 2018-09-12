A simple pagination taking props for the current page, the total number of pages and a callback, which gets called
when the page is being changed. The callback also receives the new page.

    <div style={{height: '40px'}}>
        <Pagination currentLimit={20} onLimitChange={(limit) => alert('The new limit is ' + limit)} currentPage={5} totalPages={10} onPageChange={(page) => alert('The new page number is ' + page)} />
    </div>
