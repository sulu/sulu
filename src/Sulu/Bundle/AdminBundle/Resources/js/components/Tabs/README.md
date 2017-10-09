With `Tabs` you can easily switch between different views. A `Tab` can contain basic HTML, Text and also other React
components.

```
initialState = {
    value: '1',
};

const handleChange = (value) => {
    setState({
        value: value
    });
};

const styles = {
    tabContent: {
        padding: 20,
        lineHeight: 1.5,
    },
};

<Tabs value={state.value} onChange={handleChange}>
    <Tabs.Tab label="Cheeseburger" value="1">
        <div style={styles.tabContent}>
            Think of cheeseburgers like a Tinder match. They might not all be your soulmate but you’ve gotta find out 
            to be sure. It can get a little messy and that’s just part of the fun.
            Some are cheesy, others can be a little dry, and the rare few are a disaster. 
            There are so many cheeseburgers out there it can be hard to commit to just one favourite. 
            That being said, when you know, you just know. Everyone has their perfect match. Sometimes it’s just around 
            the corner, other times you have to travel the world in search of it. Wherever your perfect cheeseburger is,
            it’s out there.
        </div>
    </Tabs.Tab>
    <Tabs.Tab label="Cupcakes" value="2">
        <div style={styles.tabContent}>
            Toffee icing cake dragée. Jelly beans donut toffee. Gummies pudding ice cream tiramisu chocolate bar oat
            cake wafer bear claw cake. Ice cream tootsie roll gummies chocolate gummi bears pie sugar plum. Pudding 
            muffin halvah topping croissant biscuit marshmallow jelly beans. Biscuit sugar plum chocolate bar jelly-o
            powder marzipan cake. Sweet sweet sweet roll sweet macaroon macaroon danish bear claw. 
            Icing donut bonbon. Donut sweet chupa chups topping. Chocolate sugar plum pastry jujubes chocolate pie.
            Macaroon macaroon dragée. Tart wafer marshmallow. Croissant halvah lollipop. Topping lemon drops halvah
            pudding oat cake topping. Sugar plum pastry brownie lollipop candy. Chocolate bar ice cream caramels
            tiramisu cake gummies pudding. Donut lemon drops topping bear claw candy canes.
        </div>
    </Tabs.Tab>
    <Tabs.Tab label="Zombies" value="3">
        <div style={styles.tabContent}>
            Zombie ipsum reversus ab viral inferno, nam rick grimes malum cerebro. De carne lumbering animata corpora 
            quaeritis. Summus brains sit​​, morbo vel maleficia? De apocalypsi gorger omero undead survivor dictum mauris.
            Hi mindless mortuis soulless creaturas, imo evil stalking monstra adventus resi dentevil vultus comedat
            cerebella viventium. Qui animated corpse, cricket bat max brucks terribilem incessu zomby. The voodoo
            sacerdos flesh eater, suscitat mortuos comedere carnem virus. Zonbi tattered for solum oculi eorum
            defunctis go lum cerebro. Nescio brains an Undead zombies. Sicut malus putrid voodoo horror. Nigh tofth
            eliv ingdead.
        </div>
    </Tabs.Tab>
</Tabs>
```
