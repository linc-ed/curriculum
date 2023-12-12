// import everything from contentful
import * as contentful from 'contentful-management'
const client = contentful.createClient({
    // This is the access token for this space. Normally you get the token in the Contentful web app
    accessToken: 'CFPAT-XgjPTYZ4tpoC0_-Z2kgYequm9AbG38UK1_i7TspkgAg',
})


// This API call will request a space with the specified ID
client.getSpace('ub60aswm9wsy').then((space) => {
    // This API call will request an environment with the specified ID
    space.getEnvironment('master').then((environment) => {
        // Now that we have an environment, we can get entries from that space
        environment.getEntries().then((entries) => {
            console.log(entries.items)
        })

        // let's get a content type
        environment.getContentType('product').then((contentType) => {
            // and now let's update its name
            contentType.name = 'New Product'
            contentType.update().then((updatedContentType) => {
                console.log('Update was successful')
            })
        })
    })
})