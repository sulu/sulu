// @flow
// import React from 'react';
import {extendObservable as mockExtendObservable} from 'mobx';
// import {shallow} from 'enzyme';
// import ResourceLocatorHistory from '../ResourceLocatorHistory';
import ResourceListStore from 'sulu-admin-bundle/stores/ResourceListStore';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/stores/ResourceListStore', () => jest.fn(function() {
    this.deleteList = jest.fn();

    mockExtendObservable(this, {
        data: [],
        loading: true,
    });
}));

// TODO reenable the tests as soon as the button in the ResourceLocatoryHistory is enabled again
// We need at least one test for jest to not fail
test('Pass props correctly to ResourceListStore', () => {
    // const resourceLocatorHistory = shallow(
    //     <ResourceLocatorHistory
    //         id={5}
    //         options={{webspace: 'sulu'}}
    //         resourceKey="history_routes"
    //     />
    // );

    expect(ResourceListStore).not.toBeCalled();
    // resourceLocatorHistory.find('Button[icon="su-process"]').simulate('click');
    // expect(ResourceListStore).toBeCalledWith('history_routes', {id: 5, webspace: 'sulu'});
});

// test('Pass correct props to Button', () => {
//     const resourceLocatorHistory = shallow(
//         <ResourceLocatorHistory
//             id={5}
//             options={{webspace: 'sulu'}}
//             resourceKey="history_routes"
//         />
//     );
//
//     expect(resourceLocatorHistory.find('Button[icon="su-process"]').props()).toEqual(expect.objectContaining({
//         disabled: false,
//         icon: 'su-process',
//         skin: 'link',
//     }));
// });
//
// test('Disable button if id is not set', () => {
//     const resourceLocatorHistory = shallow(
//         <ResourceLocatorHistory
//             id={undefined}
//             options={{webspace: 'sulu'}}
//             resourceKey="history_routes"
//         />
//     );
//
//     expect(resourceLocatorHistory.find('Button[icon="su-process"]').props()).toEqual(expect.objectContaining({
//         disabled: true,
//     }));
// });
//
// test('Show history routes in overlay', () => {
//     const resourceLocatorHistory = mount(
//         <ResourceLocatorHistory
//             id={5}
//             options={{webspace: 'sulu'}}
//             resourceKey="history_routes"
//         />
//     );
//
//     expect(resourceLocatorHistory.find('Overlay Loader')).toHaveLength(0);
//     resourceLocatorHistory.find('Button[icon="su-process"]').simulate('click');
//     expect(resourceLocatorHistory.find('Overlay Loader')).toHaveLength(1);
//
//     // $FlowFixMe
//     const resourceListStore = ResourceListStore.mock.instances[0];
//     resourceListStore.loading = false;
//     resourceListStore.data = [
//         {
//             id: 3,
//             resourcelocator: 'sulu.io/test',
//             created: '2019-04-10T13:06:16',
//         },
//         {
//             id: 6,
//             resourcelocator: 'sulu.io/testing',
//             created: '2019-04-10T16:01:12',
//         },
//     ];
//
//     resourceLocatorHistory.update();
//     expect(resourceLocatorHistory.find('Overlay').render()).toMatchSnapshot();
// });
//
// test('Reload history routes each time overlay is opened', () => {
//     const resourceLocatorHistory = mount(
//         <ResourceLocatorHistory
//             id={5}
//             options={{webspace: 'sulu'}}
//             resourceKey="history_routes"
//         />
//     );
//
//     expect(ResourceListStore).toBeCalledTimes(0);
//
//     resourceLocatorHistory.find('Button[icon="su-process"]').simulate('click');
//     expect(ResourceListStore).toBeCalledTimes(1);
//
//     resourceLocatorHistory.find('Button[icon="su-process"]').simulate('click');
//     expect(ResourceListStore).toBeCalledTimes(2);
// });
//
// test('Close overlay if button is clicked', () => {
//     const resourceLocatorHistory = mount(
//         <ResourceLocatorHistory
//             id={5}
//             options={{webspace: 'sulu'}}
//             resourceKey="history_routes"
//         />
//     );
//
//     expect(resourceLocatorHistory.find('Overlay').prop('open')).toEqual(false);
//
//     resourceLocatorHistory.find('Button[icon="su-process"]').simulate('click');
//     expect(resourceLocatorHistory.find('Overlay').prop('open')).toEqual(true);
//
//     resourceLocatorHistory.find('Overlay').prop('onConfirm')();
//     resourceLocatorHistory.update();
//     expect(resourceLocatorHistory.find('Overlay').prop('open')).toEqual(false);
// });
//
// test('Do not delete if confirmation dialog is cancelled', () => {
//     const resourceLocatorHistory = mount(
//         <ResourceLocatorHistory
//             id={5}
//             options={{webspace: 'sulu'}}
//             resourceKey="history_routes"
//         />
//     );
//
//     resourceLocatorHistory.find('Button[icon="su-process"]').simulate('click');
//
//     // $FlowFixMe
//     const resourceListStore = ResourceListStore.mock.instances[0];
//
//     resourceListStore.loading = false;
//     resourceListStore.data = [
//         {
//             id: 3,
//             resourcelocator: 'sulu.io/test',
//             created: '2019-04-10T13:06:16',
//         },
//     ];
//
//     resourceLocatorHistory.update();
//     resourceLocatorHistory.find('ButtonCell[icon="su-trash-alt"] button').prop('onClick')();
//     resourceLocatorHistory.update();
//
//     expect(resourceLocatorHistory.find('Dialog').prop('open')).toEqual(true);
//     resourceLocatorHistory.find('Dialog Button[skin="secondary"]').prop('onClick')();
//     resourceLocatorHistory.update();
//     expect(resourceLocatorHistory.find('Dialog').prop('open')).toEqual(false);
//
//     expect(resourceListStore.deleteList).not.toBeCalled();
// });
//
// test('Delete if confirmation dialog is confirmed', () => {
//     const resourceLocatorHistory = mount(
//         <ResourceLocatorHistory
//             id={5}
//             options={{webspace: 'sulu'}}
//             resourceKey="history_routes"
//         />
//     );
//
//     resourceLocatorHistory.find('Button[icon="su-process"]').simulate('click');
//
//     // $FlowFixMe
//     const resourceListStore = ResourceListStore.mock.instances[0];
//
//     resourceListStore.loading = false;
//     resourceListStore.data = [
//         {
//             id: 3,
//             resourcelocator: 'sulu.io/test',
//             created: '2019-04-10T13:06:16',
//         },
//     ];
//
//     const deleteListPromise = Promise.resolve();
//     resourceListStore.deleteList.mockReturnValue(deleteListPromise);
//
//     resourceLocatorHistory.update();
//     resourceLocatorHistory.find('ButtonCell[icon="su-trash-alt"] button').prop('onClick')();
//     resourceLocatorHistory.update();
//
//     expect(resourceLocatorHistory.find('Dialog').prop('open')).toEqual(true);
//     resourceLocatorHistory.find('Dialog Button[skin="primary"]').prop('onClick')();
//     resourceLocatorHistory.update();
//     expect(resourceLocatorHistory.find('Dialog').prop('open')).toEqual(true);
//
//     expect(resourceListStore.deleteList).toBeCalledWith([3]);
//
//     return deleteListPromise.then(() => {
//         resourceLocatorHistory.update();
//         expect(resourceLocatorHistory.find('Dialog').prop('open')).toEqual(false);
//     });
// });
