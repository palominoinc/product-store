Product Store
=============

This extension defines a product-store node which can be used to manage unlimited projects, using nested categories. A check-out and search function is available.

### Features

- unlimited nested product categories
- single list of products, manageable like a spreadsheet
- each product contains 
  - title, description, features
  - images
  - list of SKUs
- displaying products and categories using by-id routes
- friendly URLs
- customizable product search
- add-to-cart functionality

### Friendly URLs

Products can be accessed via

- <span style="font-family:courier new,courier,monospace">/product/{product-id}</span>
- <span style="font-family:courier new,courier,monospace">/category/{category-id}</span>

### Search Function

the following routes are defined, which provide a simple, yet very customizable search capability:

 <span style="font-family:courier new,courier,monospace">GET|POST /product-store/search</span>

This route calls the template **product-store-search**, which expects the search query to be set in input variable **$q**

Currently, the template implements a simple, case-insensitive search, matching any substring in the product title, however this template can easily be overridden by defining a template as such:

 ![data?command=webpalimage.download&web_na](#)

 **Default Search Template**

 ![data?command=webpalimage.download&web_na](#)

### Shopping Cart Function

The shopping cart allows for adding of any product item to a centralized shopping cart that is displayed to the user after adding an item. Item quantities can be updated in bulk, with invalid entries handled gracefully.

#### Sample Layout

####  

#### ![data?command=webpalimage.download&web_na](__resources/pastGSx2Vey8GXUe.png)  
 Note: only positive integers are allowed for quantities.

By default, the store cart can be accessed at:

- **<span style="font-family:courier new,courier,monospace">/product-store/cart</span>**

 <font face="times new roman, times, serif">The total item count can be rendered within any layout by calling the template **product-store-itemcount**</font>

 ![data?command=webpalimage.download&web_na](__resources/pastPP1MpzO4AE4t.png)

 ![data?command=webpalimage.download&web_na](__resources/past93JMVGIAzWo5.png)

####  **All defined shopping cart routes** 

####  ![data?command=webpalimage.download&web_na](__resources/pastPkhRjURviZB7.png)