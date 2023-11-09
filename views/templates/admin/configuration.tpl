<!--
/**
 * 2020 Haulmer
 *
 * NOTICE OF LICENSE
 *
 * This file is licenced under the Software License Agreement.
 * With the purchase or the installation of the software in your application
 * you accept the licence agreement.
 * You must not modify, adapt or create derivative works of this source code
 *
 * Class of data log storage module
 *
 * @author Haulmer
 * @copyright  Haulmer 2020
 * @license license.txt
 * @version: 1.0.0
 * @Email: soporte@haulmer.com
 * @Date: 22-01-2020 11:00
 * @Last Modified by:
 * @Last Modified time:
 */
-->
<div class="of-whmcs">
  <div class="wrapper">
    <div class="wrapper_content">
    {*Configuración*}
      <h1>{l s='Configuration' mod='openfactura'}</h1>
      <p>
       {*La Integración de OpenFactura enviará un correo al cliente para que pueda generar su propio documento electrónico,
        ya sea boleta o factura, a través del Autoservicio de Emisión, ingresando únicamente los datos de receptor.
        Este correo se envía al momento de aceptarse el pago de la 'Orden' (Pago Aceptado).
        Si tienes alguna duda acerca de los campos del Invoice que se utilizan para generar el documento,
        puedes revisar nuestra Documentación de Integración con Prestashop.
        *}
        {l s='OpenFactura Integration will send an email to the client so that they can generate their own electronic document, either ballot or invoice, through the Emission Self-Service, entering only the receiver data. This email is sent when the payment of the Order (Payment Accepted) is accepted. If you have any questions about the Invoice fields that are used to generate the document, you can check our Integration Documentation with Prestashop.' mod='openfactura'}
        {* <a href="#" class="linkBlue"> </a> *}
      </p>
    </div>
    <form action="" onsubmit="return sendForm(event)" id="form1">
      <section>
      {*Opciones Generales*}
        <h2>{l s='General options' mod='openfactura'}</h2>

        <div class="s-row">
            <div class="col-4">
            <div>
            <div class="form-field">
            <div class="form-field__control">
                <label for="apikey" class="form-field__label">API Key</label>
                <input id="apikey"
                    name="apikey"
                    type="text t"
                    class="form-field__input"
                    value="{$openfactura_registry['apikey']|escape:'htmlall':'UTF-8'}" >
            </div>
            <div class="form-field__hint">
                {*Ingresa tu API Key para para utilizar tus datos almacenados en OpenFactura.*}
                {l s='Enter your API Key to use your data stored in OpenFactura.' mod='openfactura'}
            </div>
            </div>
        </div>

            </div>
            <div class="col-4">
            <div class="form-apikey" id='get-apikey'>
            {*¿Dónde obtengo mi API Key?*}
                <a href="https://www.openfactura.cl/" class="linkBluee">{l s='Where do I get my API key?' mod='openfactura'}</a>
                </div>
            </div>
        </div>

        <div class="checkBoxContainer">
        <div class="container">
            <input type="checkbox" id="check0" name="demo" value="1" value="1" {if !empty($openfactura_registry) && $openfactura_registry['is_demo'] == 1}checked="checked"{/if}/>
            <label for="check0" class="md-checkbox"></label>
        </div>

        <div>
        {*Usar demostración*}
            <label for="check0" class="checkLabel">{l s='Use demo' mod='openfactura'}</label>
            <div>
            {*Al seleccionar esta opción, se habilitarán los datos de demostración almacenados en OpenFactura.*}
            {l s='Selecting this option will enable demo data stored in OpenFactura.' mod='openfactura'}
            </div>
        </div>
        </div>


        <div class="checkBoxContainer">
          <div class="md-checkboxWrapper">
            <input type="checkbox" id="check1" name="automatic39" value="1" {if !empty($openfactura_registry) && $openfactura_registry['generate_boleta'] == 1}checked="checked"{/if}/>
            <label for="check1" class="md-checkbox"></label>
          </div>

          <div>
          {*Habilitar emisión y envío automático de boletas*}
            <label for="check1" class="checkLabel">{l s='Enable automatic issuance and delivery of tickets' mod='openfactura'} </label>
                <div>
                {*Al seleccionar esta opción, una boleta electrónica se emitirá por
                defecto y será adjuntada al correo que se enviará al cliente.*}
                {l s='When selecting this option, an electronic ballot will be issued by default and will be attached to the email that will be sent to the customer.' mod='openfactura'}
                
                </div>
          </div>
        </div>

        <div class="checkBoxContainer">
          <div class="md-checkboxWrapper">
            <input type="checkbox" id="check2" name="allow33" value="1" {if !empty($openfactura_registry) && $openfactura_registry['allow_factura'] == 1}checked="checked"{/if}/>
            <label for="check2" class="md-checkbox"></label>
          </div>

          <div>
          {*Permitir al cliente ingresar datos de receptor para generar su factura*}
            <label for="check2" class="checkLabel">{l s='Allow the customer to enter receiver data to generate their invoice' mod='openfactura'}</label>
            <div>
            {*Se le permitirá al cliente la posibilidad de emitir su propia factura electrónica o bien convertir una boleta
              a factura electrónica, según sea el caso, ingresando sus datos de facturación. Se generará una Nota de
              Crédito.*}
            {l s='The customer will be allowed to issue their own electronic invoice or convert a ticket to electronic invoice, as the case may be, by entering their billing information. A Credit Note will be generated.' mod='openfactura'}
              
            </div>
          </div>
        </div>

        <div class="checkBoxContainer">
          <div class="md-checkboxWrapper">
            <input type="checkbox" id="check3" name="enableLogo" value="1" 
            {if !empty($openfactura_registry) && $openfactura_registry['show_logo'] == 1}
                checked="checked"
            {/if}/>
            <label for="check3" class="md-checkbox"></label>
          </div>

          <div>
          {*Habilitar logotipo personalizado*}
            <label for="check3" class="checkLabel">{l s='Enable custom logo' mod='openfactura'}</label>
            <div>
            {*El enlace de autoservicio que se enviará al cliente podrá ir con
              un logotipo personalizado de la empresa.*}
            {l s='The self-service link that will be sent to the customer can be delivered with a custom company logo.' mod='openfactura'}              
            {*Ver ejemplo.*}
              <a id="openDialog-preview" href="#" class="linkBlue">{l s='Example.' mod='openfactura'}</a>
              <input id="moduleLink" name="moduleLink" type="hidden" value="{$moduleLink|escape:'htmlall':'UTF-8'}modules/openfactura/views/img/preview.svg">
            </div>

        <div class="s-row">
            <div class="col-8">
                <div>
                    <div class="form-field">
                        <div class="form-field__control">
                        {*URL logo empresa*}
                            <label for="logo-url" class="form-field__label">{l s='URL logo company' mod='openfactura'}</label>
                            <input id="logo-url"
                                name="logo-url"
                                type="text t"
                                class="form-field__input"
                                value="{$openfactura_registry['link_logo']|escape:'htmlall':'UTF-8'}" >
                        </div>
                        <div class="form-field__hint">
                        {*No se mostrará el logotipo si la URL no es https. Proporciones 16:9, Dimensiones ideales de 128 X 72px.*}
                        {l s='The logo will not be displayed if the URL is not https. Proportions 16: 9, Ideal dimensions of 128 X 72px.' mod='openfactura'}
                        </div>
                    </div>
                </div>
            </div>
        </div>
      </section>
      <section>
        <div class="progressBar">
          <div class="indeterminate"></div>
        </div>
        <div class="flex-menu">
        {*Información del emisor*}
          <h2>{l s='Issuer Information' mod='openfactura'}</h2>
          <button id="update-button" class="button-flat" onclick="">{l s='Update' mod='openfactura'}</button>
        </div>
        <p>
        {*Los siguientes campos se obtienen desde el SII, a través de
          OpenFactura, y no pueden ser modificados desde acá. Si cuentas con
          sucursales, puedes seleccionar la que desees ocupar. Si has realizado
          cambios en el SII, recuerda hacer clic en 'Actualizar' para que se
          vean reflejados.*}
        {l s='The following fields are obtained from SII through OpenFactura, and cannot be modified from here. If you have multiple branches, you can select the one you want to occupy. If you have made changes at SII, remember to click on Update so that they are reflected.' mod='openfactura'}
        </p>
          <div class="s-row">
            <div class="col-2">
              <div class="form-field">
                <div class="form-field__control">
                  <label for="rut" class="form-field__label">{l s='DNI' mod='openfactura'}</label>
                  <input id="rut" type="tex t" class="form-field__input" value="{$openfactura_registry['rut']|escape:'htmlall':'UTF-8'}" disabled>
                </div>
              </div>
            </div>
            <div class="col-3">
              <div class="form-field">
                <div class="form-field__control">
                  <label for="company-name" class="form-field__label">
                  {*Razón Social*}
                  {l s='Business name' mod='openfactura'}</label>
                  <input id="company-name" type="text t" class="form-field__input" value="{$openfactura_registry['razon_social']|escape:'htmlall':'UTF-8'}" disabled>
                </div>
              </div>
            </div>
            <div class="col-3">
              <div class="form-field">
                <div class="form-field__control">
                  {*Glosa descriptiva (Ex Giro)*}
                  <label for="description" class="form-field__label">{l s='Descriptive gloss' mod='openfactura'}</label>
                  <input id="description" type="text t" class="form-field__input" value="{$openfactura_registry['glosa_descriptiva']|escape:'htmlall':'UTF-8'}" disabled>
                </div>
              </div>
            </div>
          </div>

        <div class="s-row">
          <div class="col-2">
            <div class="form-field">
              <div class="form-field__select">

                <div class="form__group">
                {*Sucursal*}
                  <label for="sucursal" class="form-field__label">{l s='Branch office' mod='openfactura'}</label>
                    <div class="form__dropdown">
                    <select name="sucursal" id="sucursal">
                      {html_options options=$sucursalesArray selected=$openfactura_registry['sucursal_active']}
                    </select>
                  </div>
                </div>

              </div>
            </div>
          </div>

          <div class="col-3">
            <div class="form-field">
              <div class="form-field__select">
                <div class="form__group">
                {*Actividad económica*}
                  <label for="actividad" class="form-field__label">{l s='Economic activity' mod='openfactura'}</label>
                    <div class="form__dropdown">
                    <select name="actividad" id="actividad">
                      {html_options options=$actividadesArray selected=$openfactura_registry['codigo_actividad_economica_active']}
                    </select>
                  </div>
                </div>

              </div>
            </div>
          </div>
        </div>
      </section>
      <div class="wrapper_content">
        <button type="submit" class="button-primary">{l s='Save' mod='openfactura'}</button>
      </div>
    </form>
  </div>
</div>