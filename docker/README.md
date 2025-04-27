# Project Testing via Docker

This project can be tested, including the provided examples, using Docker.  
To do so, please copy the documents from this directory into the project's root directory. Subsequently, update the directory paths in both the `Dockerfile` and the `docker-compose.yml` file accordingly.

To build and start the Docker containers, execute the following command:

```bash
docker compose up -d --build


```

After successful startup, the project files will be available at:
http://localhost:8095/