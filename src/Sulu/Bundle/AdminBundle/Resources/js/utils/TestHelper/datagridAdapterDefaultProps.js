// @flow

const datagridAdapterDefaultProps = {
    active: undefined,
    activeItems: undefined,
    data: [],
    disabledIds: [],
    limit: 10,
    loading: false,
    onAllSelectionChange: undefined,
    // $FlowFixMe
    onItemActivate: jest.fn(),
    onItemAdd: undefined,
    onItemClick: undefined,
    // $FlowFixMe
    onItemDeactivate: jest.fn(),
    onItemSelectionChange: undefined,
    // $FlowFixMe
    onLimitChange: jest.fn(),
    // $FlowFixMe
    onPageChange: jest.fn(),
    onRequestItemCopy: undefined,
    onRequestItemDelete: undefined,
    onRequestItemMove: undefined,
    onRequestItemOrder: undefined,
    // $FlowFixMe
    onSort: jest.fn(),
    options: {},
    page: undefined,
    pageCount: undefined,
    schema: {},
    selections: [],
    sortColumn: undefined,
    sortOrder: undefined,
};

export default datagridAdapterDefaultProps;
