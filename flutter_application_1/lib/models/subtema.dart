class Subtema {
  final int id;
  final int temaId;
  final String titulo;
  final String? descripcion;
  final String? informacion; // Este será el contenido teórico largo

  Subtema({
    required this.id,
    required this.temaId,
    required this.titulo,
    this.descripcion,
    this.informacion,
  });

  factory Subtema.fromJson(Map<String, dynamic> json) {
    return Subtema(
      id: json['id'],
      temaId: int.parse(json['tema_id'].toString()),
      titulo: json['titulo'],
      descripcion: json['descripcion'],
      informacion: json['informacion'],
    );
  }
}