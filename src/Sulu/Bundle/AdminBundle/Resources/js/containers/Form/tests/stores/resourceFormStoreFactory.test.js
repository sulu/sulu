// @flow
import ResourceStore from '../../../../stores/ResourceStore';
import ResourceFormStore from '../../stores/ResourceFormStore';
import resourceFormStoreFactory from '../../stores/resourceFormStoreFactory';

jest.mock('../../stores/ResourceFormStore', () => jest.fn());

test('Create a ResourceFormStore with a given ResourceStore', () => {
    const resourceStore = new ResourceStore('test');

    const options = {
        test: 'value',
    };

    const metadataOptions = {
        metadataTest: 'metadataValue',
    };

    const resourceFormStore = resourceFormStoreFactory.createFromResourceStore(
        resourceStore,
        'test',
        options,
        metadataOptions
    );

    expect(ResourceFormStore).toBeCalledWith(resourceStore, 'test', options, metadataOptions);
    // $FlowFixMe
    expect(resourceFormStore).toBe(ResourceFormStore.mock.instances[0]);
});
