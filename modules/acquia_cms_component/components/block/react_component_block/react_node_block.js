(function (drupalApi) {
  let drupalApiOj = new drupalApi();
  const Node = createReactClass({
    getInitialState: function() {
      this.state = {
        data: [],
        loaded: false,
        node: {}
      };
      return {};
    },
    loadBody(e) {
      e.preventDefault();
      let data = this.state.data;
      for (let key in data) {
        if (data[key].id == e.target.id) {
          this.setState({node : data[key] });
          break;
        }
      }
      return false;
    },
    clearBody(e) {
      e.preventDefault();
      this.setState({ node : {} });
    },
    componentDidMount() {
      const attributes = this.props.attributes;
      let _this = this, params = { sort: "-created" };
      drupalApiOj.setEndpoint("node/" + attributes['data-type']);
      if (attributes.hasOwnProperty('data-display-item')) {
        params['page'] = {
          "limit": attributes['data-display-item'],
          "offset": "5"
        };
      }
      drupalApiOj.setParams(params);
      drupalApiOj.callApi((data) => _this.setState({ data: data.data, loaded: true }));
    },
    render() {
      const { data, loaded, node } = this.state;
      const nodeLoaded = (node && Object.keys(node).length > 0) ? true : false;
      const nodeSummary = (node && Object.keys(node).length > 0 && node.attributes.body.summary) ? node.attributes.body.summary: "<i>No summary.</i>";
      if (!loaded) {
        return <div className="loader" />;
      }
      if (data.length === 0) {
        return <i>No results</i>;
      }
      return (
        <div>
          { (!nodeLoaded) ? (
            <ul className={(!nodeLoaded) ? 'toggleIn': 'toggleOut'}>
              {data.map(article =>
                <li class=" article-list coh-style-card-text-light-background">
                  <a href={article.attributes.path.alias} onClick={this.loadBody}
                     id={article.id} class="coh-link card-link titles">{article.attributes.title}</a>
                </li>
              )}
            </ul>): (<div className={(!nodeLoaded) ? 'toggleOut': 'toggleIn'}>
            <a onClick={this.clearBody} href="#">Back</a>
            <div class="node-summary" dangerouslySetInnerHTML={{__html: nodeSummary}} />
          </div>)
          }
        </div>
      );
    }
  });
  let elements = document.getElementsByClassName('react_component_block');
  for (let i = 0; i < elements.length; ++i) {
    let element = elements[i];
    let attributes = {};
    for (let j = 0, atts = element.attributes, n = atts.length; j < n; j++) {
      if (atts[j].nodeName !== "class" && atts[j].nodeName !== "id" ) {
        attributes[atts[j].nodeName]= atts[j].nodeValue;
      }
    }
    ReactDOM.render(<Node attributes={attributes} />, element);
  }
})(DrupalApi);
