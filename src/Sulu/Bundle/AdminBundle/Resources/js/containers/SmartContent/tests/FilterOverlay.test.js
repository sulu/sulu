// @flow
import React from 'react';
import {shallow, mount} from 'enzyme';
import SmartContentStore from '../stores/SmartContentStore';
// TODO update path when component is extracted
import DatagridOverlay from '../../../containers/Selection/DatagridOverlay';
import MultiAutoComplete from '../../../containers/MultiAutoComplete';
import FilterOverlay from '../FilterOverlay';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../containers/MultiAutoComplete', () => jest.fn(() => null));
// TODO update path when component is extracted
jest.mock('../../../containers/Selection/DatagridOverlay', () => jest.fn(() => null));

test('Do not display if open is set to false', () => {
    const smartContentStore = new SmartContentStore();
    const filterOverlay = shallow(
        <FilterOverlay
            dataSourceAdapter={undefined}
            dataSourceResourceKey={undefined}
            onClose={jest.fn()}
            open={false}
            presentations={{}}
            sortings={{}}
            smartContentStore={smartContentStore}
        />
    );

    expect(filterOverlay.find('Overlay').prop('open')).toEqual(false);
});

test('Fill all fields using and update SmartContentStore on confirm', () => {
    const smartContentStore = new SmartContentStore();
    const closeSpy = jest.fn();

    const filterOverlay = mount(
        <FilterOverlay
            dataSourceAdapter="table"
            dataSourceResourceKey="pages"
            onClose={closeSpy}
            open={true}
            presentations={{
                small: 'Small',
                large: 'Large',
            }}
            sortings={{
                title: 'Title',
                changed: 'Changed',
            }}
            smartContentStore={smartContentStore}
        />
    );

    filterOverlay.find('Button[children="sulu_admin.choose_data_source"]').prop('onClick')();
    filterOverlay.update();
    expect(filterOverlay.find(DatagridOverlay).find({resourceKey: 'pages'}).prop('open')).toEqual(true);
    filterOverlay.find(DatagridOverlay).find({resourceKey: 'pages'}).prop('onConfirm')([{id: 2, url: '/test'}]);
    filterOverlay.update();
    expect(filterOverlay.find(DatagridOverlay).find({resourceKey: 'pages'}).prop('open')).toEqual(false);
    expect(filterOverlay.find('section').at(1).find('label[className="description"]').text())
        .toEqual('sulu_admin.data_source: /test');

    filterOverlay.find('Checkbox[children="sulu_admin.include_sub_elements"]').prop('onChange')(true);
    filterOverlay.update();
    expect(filterOverlay.find('Checkbox[children="sulu_admin.include_sub_elements"]').prop('checked')).toEqual(true);

    filterOverlay.find('Button[children="sulu_admin.choose_categories"]').prop('onClick')();
    filterOverlay.update();
    expect(filterOverlay.find(DatagridOverlay).find({resourceKey: 'categories'}).prop('open')).toEqual(true);
    filterOverlay.find(DatagridOverlay).find({resourceKey: 'categories'}).prop('onConfirm')([
        {id: 1, name: 'Test1'},
        {id: 3, name: 'Test2'},
    ]);
    filterOverlay.update();
    expect(filterOverlay.find(DatagridOverlay).find({resourceKey: 'categories'}).prop('open')).toEqual(false);
    expect(filterOverlay.find('section').at(2).find('label[className="description"]').text())
        .toEqual('sulu_category.categories: Test1, Test2');

    filterOverlay.find('div[className="categories"]').find('SingleSelect').prop('onChange')('and');
    filterOverlay.update();
    expect(filterOverlay.find('div[className="categories"]').find('SingleSelect').prop('value')).toEqual('and');

    filterOverlay.find(MultiAutoComplete).prop('onChange')(['Test1', 'Test3']);
    filterOverlay.update();
    expect(filterOverlay.find(MultiAutoComplete).prop('value')).toEqual(['Test1', 'Test3']);

    filterOverlay.find('div[className="tags"]').find('SingleSelect').prop('onChange')('or');
    filterOverlay.update();
    expect(filterOverlay.find('div[className="tags"]').find('SingleSelect').prop('value')).toEqual('or');

    filterOverlay.find('Checkbox[children="sulu_admin.use_target_groups"]').prop('onChange')(false);
    filterOverlay.update();
    expect(filterOverlay.find('Checkbox[children="sulu_admin.use_target_groups"]').prop('checked')).toEqual(false);

    filterOverlay.find('div[className="sortColumn"]').find('SingleSelect').prop('onChange')('changed');
    filterOverlay.update();
    expect(filterOverlay.find('div[className="sortColumn"]').find('SingleSelect').prop('value')).toEqual('changed');

    filterOverlay.find('div[className="sortOrder"]').find('SingleSelect').prop('onChange')('asc');
    filterOverlay.update();
    expect(filterOverlay.find('div[className="sortOrder"]').find('SingleSelect').prop('value')).toEqual('asc');

    filterOverlay.find('div[className="presentation"]').find('SingleSelect').prop('onChange')('large');
    filterOverlay.update();
    expect(filterOverlay.find('div[className="presentation"]').find('SingleSelect').prop('value')).toEqual('large');

    filterOverlay.find('div[className="limit"] Number').prop('onChange')(7);
    filterOverlay.update();
    expect(filterOverlay.find('div[className="limit"] Number').prop('value')).toEqual(7);

    expect(smartContentStore.dataSource).toEqual(undefined);
    expect(smartContentStore.includeSubElements).toEqual(undefined);
    expect(smartContentStore.categories).toEqual(undefined);
    expect(smartContentStore.categoryOperator).toEqual(undefined);
    expect(smartContentStore.tags).toEqual(undefined);
    expect(smartContentStore.tagOperator).toEqual(undefined);
    expect(smartContentStore.audienceTargeting).toEqual(undefined);
    expect(smartContentStore.sortBy).toEqual(undefined);
    expect(smartContentStore.sortOrder).toEqual(undefined);
    expect(smartContentStore.presentation).toEqual(undefined);
    expect(smartContentStore.limit).toEqual(undefined);

    filterOverlay.find('Overlay').prop('onConfirm')();

    expect(smartContentStore.dataSource).toEqual({id: 2, url: '/test'});
    expect(smartContentStore.includeSubElements).toEqual(true);
    expect(smartContentStore.categories).toEqual([{id: 1, name: 'Test1'}, {id: 3, name: 'Test2'}]);
    expect(smartContentStore.categoryOperator).toEqual('and');
    expect(smartContentStore.tags).toEqual(['Test1', 'Test3']);
    expect(smartContentStore.tagOperator).toEqual('or');
    expect(smartContentStore.audienceTargeting).toEqual(false);
    expect(smartContentStore.sortBy).toEqual('changed');
    expect(smartContentStore.sortOrder).toEqual('asc');
    expect(smartContentStore.presentation).toEqual('large');
    expect(smartContentStore.limit).toEqual(7);

    expect(closeSpy).toBeCalledWith();
});

test('Prefill all fields with correct values', () => {
    const smartContentStore = new SmartContentStore();
    smartContentStore.dataSource = {id: 4, url: '/home'};
    smartContentStore.includeSubElements = true;
    smartContentStore.categories = [{id: 1, name: 'Test1'}, {id: 5, name: 'Test3'}];
    smartContentStore.categoryOperator = 'or';
    smartContentStore.tags = ['Test5', 'Test7'];
    smartContentStore.tagOperator = 'and';
    smartContentStore.audienceTargeting = true;
    smartContentStore.sortBy = 'created';
    smartContentStore.sortOrder = 'desc';
    smartContentStore.presentation = 'small';
    smartContentStore.limit = 8;

    const filterOverlay = mount(
        <FilterOverlay
            dataSourceAdapter="table"
            dataSourceResourceKey="pages"
            onClose={jest.fn()}
            open={true}
            presentations={{
                small: 'Small',
                large: 'Large',
            }}
            sortings={{
                title: 'Title',
                created: 'Created',
            }}
            smartContentStore={smartContentStore}
        />
    );

    expect(filterOverlay.find('section').at(1).find('label[className="description"]').text())
        .toEqual('sulu_admin.data_source: /home');
    expect(filterOverlay.find(DatagridOverlay).find({resourceKey: 'pages'}).prop('preSelectedItems'))
        .toEqual([{id: 4, url: '/home'}]);
    expect(filterOverlay.find('Checkbox[children="sulu_admin.include_sub_elements"]').prop('checked')).toEqual(true);

    expect(filterOverlay.find('section').at(2).find('label[className="description"]').text())
        .toEqual('sulu_category.categories: Test1, Test3');
    expect(filterOverlay.find(DatagridOverlay).find({resourceKey: 'categories'}).prop('preSelectedItems'))
        .toEqual([{id: 1, name: 'Test1'}, {id: 5, name: 'Test3'}]);
    expect(filterOverlay.find('div[className="categories"]').find('SingleSelect').prop('value')).toEqual('or');

    expect(filterOverlay.find(MultiAutoComplete).prop('value')).toEqual(['Test5', 'Test7']);
    expect(filterOverlay.find('div[className="tags"]').find('SingleSelect').prop('value')).toEqual('and');

    expect(filterOverlay.find('Checkbox[children="sulu_admin.use_target_groups"]').prop('checked')).toEqual(true);

    expect(filterOverlay.find('div[className="sortColumn"]').find('SingleSelect').prop('value')).toEqual('created');
    expect(filterOverlay.find('div[className="sortOrder"]').find('SingleSelect').prop('value')).toEqual('desc');

    expect(filterOverlay.find('div[className="presentation"]').find('SingleSelect').prop('value')).toEqual('small');
    expect(filterOverlay.find('div[className="limit"] Number').prop('value')).toEqual(8);
});

test('Reset all fields when reset action is clicked', () => {
    const smartContentStore = new SmartContentStore();
    smartContentStore.dataSource = {id: 4, url: '/home'};
    smartContentStore.includeSubElements = true;
    smartContentStore.categories = [{id: 1, name: 'Test1'}, {id: 5, name: 'Test3'}];
    smartContentStore.categoryOperator = 'or';
    smartContentStore.tags = ['Test5', 'Test7'];
    smartContentStore.tagOperator = 'and';
    smartContentStore.audienceTargeting = true;
    smartContentStore.sortBy = 'created';
    smartContentStore.sortOrder = 'desc';
    smartContentStore.presentation = 'large';
    smartContentStore.limit = 5;

    const filterOverlay = mount(
        <FilterOverlay
            dataSourceAdapter="table"
            dataSourceResourceKey="pages"
            onClose={jest.fn()}
            open={true}
            presentations={{
                small: 'Small',
                large: 'Large',
            }}
            sortings={{
                title: 'Title',
                created: 'Created',
            }}
            smartContentStore={smartContentStore}
        />
    );

    filterOverlay.find('Overlay').prop('actions')[0].onClick();
    filterOverlay.update();

    expect(filterOverlay.instance().dataSource).toEqual(undefined);
    expect(filterOverlay.instance().includeSubElements).toEqual(undefined);
    expect(filterOverlay.instance().categories).toEqual(undefined);
    expect(filterOverlay.instance().categoryOperator).toEqual(undefined);
    expect(filterOverlay.instance().tags).toEqual(undefined);
    expect(filterOverlay.instance().tagOperator).toEqual(undefined);
    expect(filterOverlay.instance().audienceTargeting).toEqual(undefined);
    expect(filterOverlay.instance().sortBy).toEqual(undefined);
    expect(filterOverlay.instance().sortOrder).toEqual(undefined);
    expect(filterOverlay.instance().presentation).toEqual(undefined);
    expect(filterOverlay.instance().limit).toEqual(undefined);
});
